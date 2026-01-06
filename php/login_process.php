<?php
/**
 * BACKSTAGE CENA - Processar Login
 * 
 * Este arquivo processa o formulário de login,
 * verifica as credenciais no banco de dados
 * e cria a sessão do usuário.
 */

// Incluir arquivo de configuração e conexão
require_once 'config.php';

// Verificar se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.html");
    exit();
}

// ============================================
// RECEBER E LIMPAR DADOS DO FORMULÁRIO
// ============================================

$email = limpar_entrada($_POST['email']);
$senha = $_POST['senha']; // Senha não precisa de sanitização (será verificada com hash)
$lembrar = isset($_POST['lembrar']) ? true : false;

// ============================================
// VALIDAÇÕES BÁSICAS
// ============================================

$erros = [];

// Validar email
if (empty($email)) {
    $erros[] = "O email é obrigatório.";
} elseif (!validar_email($email)) {
    $erros[] = "Email inválido.";
}

// Validar senha
if (empty($senha)) {
    $erros[] = "A senha é obrigatória.";
} elseif (strlen($senha) < 6) {
    $erros[] = "A senha deve ter pelo menos 6 caracteres.";
}

// Se houver erros, redirecionar de volta
if (!empty($erros)) {
    $_SESSION['erro_login'] = implode("<br>", $erros);
    header("Location: ../login.html?erro=1");
    exit();
}

// ============================================
// BUSCAR USUÁRIO NO BANCO DE DADOS
// ============================================

try {
    // Preparar consulta SQL (previne SQL Injection)
    $sql = "SELECT id_usuario, nome_completo, nome_artistico, email, senha, ativo, email_verificado, foto_perfil 
            FROM usuario 
            WHERE email = ? 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Verificar se encontrou o usuário
    if ($result->num_rows === 0) {
        // Email não encontrado
        $_SESSION['erro_login'] = "Email ou senha incorretos.";
        header("Location: ../login.html?erro=1");
        exit();
    }
    
    // Pegar dados do usuário
    $usuario = $result->fetch_assoc();
    
    // ============================================
    // VERIFICAR SENHA
    // ============================================
    
    if (!verificar_senha($senha, $usuario['senha'])) {
        // Senha incorreta
        $_SESSION['erro_login'] = "Email ou senha incorretos.";
        header("Location: ../login.html?erro=1");
        exit();
    }
    
    // ============================================
    // VERIFICAR SE A CONTA ESTÁ ATIVA
    // ============================================
    
    if ($usuario['ativo'] == 0) {
        $_SESSION['erro_login'] = "Sua conta está desativada. Entre em contato com o suporte.";
        header("Location: ../login.html?erro=1");
        exit();
    }
    
    // ============================================
    // LOGIN BEM-SUCEDIDO! CRIAR SESSÃO
    // ============================================
    
    // Regenerar ID da sessão (segurança contra session fixation)
    session_regenerate_id(true);
    
    // Guardar dados do usuário na sessão
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['usuario_nome'] = $usuario['nome_completo'];
    $_SESSION['usuario_nome_artistico'] = $usuario['nome_artistico'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_foto'] = $usuario['foto_perfil'];
    $_SESSION['logado'] = true;
    $_SESSION['login_timestamp'] = time();
    
    // ============================================
    // ATUALIZAR ÚLTIMO ACESSO NO BANCO
    // ============================================
    
    $sql_update = "UPDATE usuario SET ultimo_acesso = NOW() WHERE id_usuario = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $usuario['id_usuario']);
    $stmt_update->execute();
    
    // ============================================
    // DEFINIR COOKIE "LEMBRAR-ME" (se marcado)
    // ============================================
    
    if ($lembrar) {
        // Cookie válido por 30 dias
        $cookie_value = base64_encode($usuario['id_usuario'] . ':' . $usuario['email']);
        setcookie('lembrar_backstage', $cookie_value, time() + (30 * 24 * 60 * 60), '/');
    }
    
    // ============================================
    // REDIRECIONAR PARA PÁGINA DE PERFIL
    // ============================================
    
    $_SESSION['sucesso_login'] = "Login realizado com sucesso! Bem-vindo(a), " . $usuario['nome_artistico'] . "!";
    header("Location: ../perfil.php");
    exit();
    
} catch (Exception $e) {
    // Erro no banco de dados
    $_SESSION['erro_login'] = "Erro ao processar login. Tente novamente.";
    error_log("Erro no login: " . $e->getMessage()); // Log do erro
    header("Location: ../login.html?erro=1");
    exit();
}

// Fechar conexão
$stmt->close();
$conn->close();

?>
