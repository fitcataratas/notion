<?php
declare(strict_types=1);

/** Monta as páginas: o conteúdo de cada tela entra dentro do "layout" (menu lateral e topo). */
final class View
{
    public static function render(string $tela, array $dados = [], bool $comLayout = true): void
    {
        header('Content-Type: text/html; charset=utf-8');
        extract($dados, EXTR_SKIP);
        ob_start();
        require VIEWS . "/$tela.php";
        $conteudo = ob_get_clean();
        if ($comLayout) {
            $usuario = Auth::usuario();
            $titulo ??= 'Gestão De Angeli';
            require VIEWS . '/layout.php';
        } else {
            echo $conteudo;
        }
    }

    public static function json(mixed $dados, int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function naoEncontrado(): never
    {
        http_response_code(404);
        if (Auth::usuario()) {
            self::render('erro', ['titulo' => 'Página não encontrada', 'mensagem' => 'Essa página não existe ou foi removida.']);
        } else {
            self::render('erro', ['titulo' => 'Página não encontrada', 'mensagem' => 'Essa página não existe.'], false);
        }
        exit;
    }

    public static function proibido(): never
    {
        http_response_code(403);
        self::render('erro', ['titulo' => 'Sem permissão', 'mensagem' => 'Você não tem permissão para acessar esta página.']);
        exit;
    }
}
