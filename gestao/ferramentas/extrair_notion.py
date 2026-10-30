#!/usr/bin/env python3
"""
Extrai do backup do Notion (exportação CSV em Markdown) os dados da Central de
Tarefas e a lista de pessoas, e grava arquivos JSON prontos para importação.

Por que ler as páginas (.md) além do CSV?
O Notion NÃO exporta nos CSVs os campos do tipo "Pessoa" (Responsável,
Solicitante...). Eles só aparecem dentro da página de cada tarefa. Por isso o
CSV é usado como lista oficial das linhas (88 tarefas) e as páginas como fonte
de todos os campos.

Uso:
    python3 -I extrair_notion.py <pasta-da-exportacao-csv> <pasta-de-saida>
"""
import csv
import glob
import json
import os
import re
import sys
from collections import Counter, defaultdict
from datetime import datetime

csv.field_size_limit(10**9)

MESES = {
    "January": 1, "February": 2, "March": 3, "April": 4, "May": 5, "June": 6,
    "July": 7, "August": 8, "September": 9, "October": 10, "November": 11, "December": 12,
}

# Campos de texto da página -> nome interno
CAMPOS = {
    "BRIEFING": "briefing",
    "CONTEÚDO": "conteudo",
    "FORMATO E ESPECIFICAÇÕES": "formato_especificacoes",
    "OBJETIVO DA PEÇA": "objetivo_peca",
    "OBSERVAÇÕES DO SOLICITANTE": "observacoes_solicitante",
    "PASTA DE MATERIAIS": "pasta_materiais",
    "PÚBLICO ALVO": "publico_alvo",
    "REFERÊNCIAS": "referencias",
    "TIPO DE MATERIAL": "tipo_material",
    "ETAPA MARKETING E DESIGN": "etapa_mkt_design",
    "TIPO": "tipo",
}
# Nomes de propriedade conhecidos da Central de Tarefas. Valores podem ocupar
# várias linhas (ex.: CONTEÚDO), então só estas chaves iniciam uma propriedade.
CHAVES_CONHECIDAS = [
    "ATIVIDADE", "BRIEFING", "CONTEÚDO", "DATA", "DATA DE PUBLICAÇÃO/IMPRESSÃO",
    "ETAPA MARKETING E DESIGN", "FORMATO E ESPECIFICAÇÕES", "OBJETIVO DA PEÇA",
    "OBSERVAÇÕES DO SOLICITANTE", "PASTA DE MATERIAIS", "PRAZO SOLICITADO",
    "PRIORIDADE", "PROJETO", "PÚBLICO ALVO", "REFERÊNCIAS", "RESPONSÁVEL",
    "RESPONSÁVEL PELA APROVAÇÃO", "SETOR", "SETOR SOLICITANTE", "SOLICITANTE",
    "STATUS", "TIPO", "TIPO DE MATERIAL",
]
LINHA_PROP = re.compile(
    r"^(" + "|".join(re.escape(k) for k in sorted(CHAVES_CONHECIDAS, key=len, reverse=True)) + r"): ?(.*)$"
)


def norm_nome(s):
    return re.sub(r"\s+", " ", (s or "").strip())


def parse_data(txt):
    """'July 14, 2026 15:30 (GMT-3)' -> ('2026-07-14', '15:30'); aceita intervalos 'A → B'."""
    if not txt:
        return None, None, None
    partes = [p.strip() for p in txt.split("→")]

    def um(p):
        m = re.match(r"([A-Za-z]+) (\d{1,2}), (\d{4})(?: (\d{1,2}):(\d{2}))?", p)
        if not m or m.group(1) not in MESES:
            return None, None
        d = datetime(int(m.group(3)), MESES[m.group(1)], int(m.group(2))).strftime("%Y-%m-%d")
        h = f"{int(m.group(4)):02d}:{m.group(5)}" if m.group(4) else None
        return d, h

    ini, hora = um(partes[0])
    fim = um(partes[1])[0] if len(partes) > 1 else None
    return ini, hora, fim


def lista(valor):
    return [norm_nome(x) for x in (valor or "").split(",") if norm_nome(x)]


NORMALIZA = {
    "prioridade": {"Media": "Média", "Média": "Média", "Alta": "Alta", "Baixa": "Baixa"},
}


def ler_pagina(caminho):
    linhas = open(caminho, encoding="utf-8").read().split("\n")
    titulo = linhas[0][2:].strip() if linhas and linhas[0].startswith("# ") else ""
    # Localiza as linhas que iniciam uma propriedade (as propriedades vêm
    # em ordem alfabética, antes do corpo da página).
    marcas = [(i, LINHA_PROP.match(l)) for i, l in enumerate(linhas) if i > 0 and LINHA_PROP.match(l)]
    props = {}
    for n, (i, m) in enumerate(marcas):
        if n + 1 < len(marcas):
            # valor pode continuar nas linhas seguintes até a próxima propriedade
            resto = linhas[i + 1:marcas[n + 1][0]]
            valor = (m.group(2) + "\n" + "\n".join(resto)).strip()
        else:
            valor = m.group(2).strip()  # última: o que vem depois é o corpo
        props[m.group(1)] = valor
    if marcas:
        corpo = "\n".join(linhas[marcas[-1][0] + 1:]).strip()
    else:
        corpo = "\n".join(linhas[1:]).strip()
    return titulo, props, corpo


def main(raiz_csv, saida):
    os.makedirs(saida, exist_ok=True)

    pasta_central = glob.glob(os.path.join(raiz_csv, "**", "Central de Tarefas e Agenda"), recursive=True)[0]
    csv_central = glob.glob(os.path.join(raiz_csv, "**", "Central de Tarefas e Agenda*_all.csv"), recursive=True)[0]
    csv_pessoas = glob.glob(os.path.join(raiz_csv, "**", "People*_all.csv"), recursive=True)[0]

    linhas_csv = list(csv.DictReader(open(csv_central, encoding="utf-8-sig", newline="")))
    paginas = defaultdict(list)
    sub_paginas = []
    for f in sorted(glob.glob(os.path.join(pasta_central, "**", "*.md"), recursive=True)):
        t, p, c = ler_pagina(f)
        if os.path.dirname(f) != pasta_central:
            sub_paginas.append((os.path.basename(os.path.dirname(f)), t, p, c))
            continue
        paginas[t].append((p, c, f))

    tarefas, sem_pagina = [], []
    usados = Counter()
    for linha in linhas_csv:
        titulo = linha["ATIVIDADE"].strip()
        cand = paginas.get(titulo, [])
        idx = usados[titulo]
        usados[titulo] += 1
        if idx >= len(cand):
            sem_pagina.append(titulo)
            props, corpo = {}, ""
            # Sem página: usa o que o CSV tem (sem pessoas)
            for k, v in linha.items():
                if v:
                    props[k] = v
        else:
            props, corpo, _ = cand[idx]

        ini, hora, fim = parse_data(props.get("DATA"))
        prazo = parse_data(props.get("PRAZO SOLICITADO"))[0]
        publicacao = parse_data(props.get("DATA DE PUBLICAÇÃO/IMPRESSÃO"))[0]

        t = {
            "titulo": titulo,
            "status": props.get("STATUS") or "A fazer",
            "prioridade": NORMALIZA["prioridade"].get(props.get("PRIORIDADE", ""), None) if props.get("PRIORIDADE") else None,
            "data_inicio": ini,
            "hora_inicio": hora,
            "data_fim": fim,
            "prazo_solicitado": prazo,
            "data_publicacao": publicacao,
            "setores": lista(props.get("SETOR")),
            "projetos": lista(props.get("PROJETO")),
            "responsaveis": lista(props.get("RESPONSÁVEL")),
            "solicitantes": lista(props.get("SOLICITANTE")),
            "aprovadores": lista(props.get("RESPONSÁVEL PELA APROVAÇÃO")),
            "setor_solicitante": lista(props.get("SETOR SOLICITANTE")),
            "corpo": corpo,
        }
        for k_nt, k_int in CAMPOS.items():
            t[k_int] = props.get(k_nt) or None
        tarefas.append(t)

    # Subpáginas (ex.: "Atualizar CRM/Prospecção"): o texto vira parte do corpo da tarefa-mãe
    anexadas = []
    for pasta, t, p, c in sub_paginas:
        mae = next((x for x in tarefas if x["titulo"] == pasta), None)
        texto = f"\n\n--- Subpágina: {t} ---\n" + "\n".join(f"{k}: {v}" for k, v in p.items()) + ("\n" + c if c else "")
        if mae:
            mae["corpo"] = (mae["corpo"] or "") + texto
            anexadas.append((pasta, t))

    # Pessoas
    pessoas = []
    for r in csv.DictReader(open(csv_pessoas, encoding="utf-8-sig", newline="")):
        nome = norm_nome(r["Name"])
        pessoas.append({"nome": nome, "tipo_notion": r["Membership Type"]})

    json.dump(tarefas, open(os.path.join(saida, "tarefas.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    json.dump(pessoas, open(os.path.join(saida, "pessoas.json"), "w", encoding="utf-8"), ensure_ascii=False, indent=1)

    # Relatório de conferência
    print(f"Linhas no CSV (tarefas oficiais): {len(linhas_csv)}")
    print(f"Tarefas gravadas: {len(tarefas)}")
    print(f"Sem página correspondente (usam só o CSV): {sem_pagina}")
    print(f"Subpáginas anexadas ao texto da tarefa-mãe: {anexadas}")
    print(f"Pessoas: {len(pessoas)}")
    print("Com responsável:", sum(1 for t in tarefas if t["responsaveis"]))
    print("Com mais de um responsável:", sum(1 for t in tarefas if len(t["responsaveis"]) > 1))
    print("Com data:", sum(1 for t in tarefas if t["data_inicio"]))
    print("Status:", dict(Counter(t["status"] for t in tarefas)))
    nomes_tarefas = {n for t in tarefas for n in t["responsaveis"] + t["solicitantes"] + t["aprovadores"] if n}
    print("Nomes usados nas tarefas que NÃO estão na lista de pessoas:",
          sorted(nomes_tarefas - {p["nome"] for p in pessoas}))


if __name__ == "__main__":
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    main(sys.argv[1], sys.argv[2])
