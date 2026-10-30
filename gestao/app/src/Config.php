<?php
declare(strict_types=1);

/**
 * Listas fixas do sistema (as "opções" que o Notion tinha nas colunas de seleção).
 * Setores e projetos ficam no banco (telas de administração). As listas abaixo
 * mudam raramente; alterar exige editar este arquivo.
 */
final class Config
{
    /** Status da tarefa, na ordem das colunas do quadro. Cores seguem o memorial. */
    public const STATUS = ['A fazer', 'Em andamento', 'Aguardando', 'Concluído', 'Cancelado'];
    public const STATUS_ABERTOS = ['A fazer', 'Em andamento', 'Aguardando'];

    public const PRIORIDADES = ['Alta', 'Média', 'Baixa'];
    public const TIPOS = ['Tarefa', 'Reunião', 'Post', 'Publicação', 'Reels'];
    public const TIPOS_MATERIAL = ['Apresentação', 'Email Marketing', 'Material Impresso', 'Reels', 'Outro'];
    public const ETAPAS_MKT = ['Nova solicitação', 'Aguardando informações', 'Aprovada para o Design', 'Em criação', 'Concluída'];

    public const PAPEIS = ['admin' => 'Administrador', 'equipe' => 'Equipe interna', 'externo' => 'Externo'];

    // Segurança
    public const LOGIN_MAX_FALHAS_EMAIL = 5;     // por e-mail, na janela abaixo
    public const LOGIN_MAX_FALHAS_IP = 25;       // por endereço de rede, na janela abaixo
    public const LOGIN_JANELA_MIN = 15;
    public const SESSAO_OCIOSA_SEG = 4 * 3600;   // sai sozinho após 4 h sem usar
    public const SESSAO_MAXIMA_SEG = 12 * 3600;  // e no máximo 12 h desde o login
    public const SENHA_MIN = 10;
}
