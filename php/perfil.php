<?php
// Incluir configuração e verificar login
require_once 'php/config.php';
verificar_login();

// Pegar ID do usuário logado
$id_usuario = $_SESSION['usuario_id'];

// ============================================
// BUSCAR DADOS DO USUÁRIO NO BANCO
// ============================================

$sql = "SELECT 
    u.id_usuario,
    u.nome_completo,
    u.nome_artistico,
    u.email,
    u.telefone,
    u.cidade,
    u.estado,
    u.bairro,
    u.area_atuacao,
    u.anos_experiencia,
    u.biografia,
    u.foto_perfil,
    u.created_at
FROM usuario u
WHERE u.id_usuario = ?
LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Usuário não encontrado!");
}

$usuario = $result->fetch_assoc();
$stmt->close();

// ============================================
// BUSCAR INSTRUMENTOS DO USUÁRIO
// ============================================

$sql_inst = "SELECT i.nome_instrumento, ui.principal
FROM usuario_instrumento ui
INNER JOIN instrumento i ON ui.id_instrumento = i.id_instrumento
WHERE ui.id_usuario = ?
ORDER BY ui.principal DESC";

$stmt_inst = $conn->prepare($sql_inst);
$stmt_inst->bind_param("i", $id_usuario);
$stmt_inst->execute();
$result_inst = $stmt_inst->get_result();

$instrumentos = [];
while ($row = $result_inst->fetch_assoc()) {
    $instrumentos[] = $row;
}
$stmt_inst->close();

// ============================================
// BUSCAR GÊNEROS DO USUÁRIO
// ============================================

$sql_gen = "SELECT g.nome_genero, ug.preferencia
FROM usuario_genero ug
INNER JOIN genero g ON ug.id_genero = g.id_genero
WHERE ug.id_usuario = ?
ORDER BY ug.preferencia DESC";

$stmt_gen = $conn->prepare($sql_gen);
$stmt_gen->bind_param("i", $id_usuario);
$stmt_gen->execute();
$result_gen = $stmt_gen->get_result();

$generos = [];
while ($row = $result_gen->fetch_assoc()) {
    $generos[] = $row;
}
$stmt_gen->close();

// ============================================
// BUSCAR DISPONIBILIDADE DO USUÁRIO
// ============================================

$sql_disp = "SELECT d.periodo
FROM usuario_disponibilidade ud
INNER JOIN disponibilidade d ON ud.id_disponibilidade = d.id_disponibilidade
WHERE ud.id_usuario = ?";

$stmt_disp = $conn->prepare($sql_disp);
$stmt_disp->bind_param("i", $id_usuario);
$stmt_disp->execute();
$result_disp = $stmt_disp->get_result();

$disponibilidade = [];
while ($row = $result_disp->fetch_assoc()) {
    $disponibilidade[] = $row['periodo'];
}
$stmt_disp->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Backstage Cena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background-image: url('imagens/background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .perfil-header {
            background: rgba(0, 0, 0, 0.75);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .profile-info {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            font-weight: 700;
            border: 5px solid rgba(139, 92, 246, 0.3);
        }

        .profile-details h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 36px;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .profile-details .username {
            color: #8b5cf6;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .profile-meta {
            display: flex;
            gap: 25px;
            color: #d1d5db;
            font-size: 14px;
            margin-top: 15px;
        }

        .profile-meta i {
            color: #8b5cf6;
            margin-right: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .perfil-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .perfil-card {
            background: rgba(0, 0, 0, 0.75);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            color: #8b5cf6;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tag {
            padding: 8px 16px;
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 20px;
            color: #a78bfa;
            font-size: 14px;
            font-weight: 500;
        }

        .tag.principal {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(109, 40, 217, 0.3));
            border-color: #8b5cf6;
        }

        .biografia {
            color: #d1d5db;
            line-height: 1.6;
            font-size: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #d1d5db;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #9ca3af;
        }

        .empty-state {
            text-align: center;
            color: #9ca3af;
            padding: 30px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .perfil-content {
                grid-template-columns: 1fr;
            }

            .profile-info {
                flex-direction: column;
            }

            .header-top {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER DO PERFIL -->
        <div class="perfil-header">
            <div class="header-top">
                <div class="profile-info">
                    <div class="profile-photo">
                        <?php 
                        // Primeira letra do nome
                        echo strtoupper(substr($usuario['nome_completo'], 0, 1)); 
                        ?>
                    </div>
                    <div class="profile-details">
                        <h1><?php echo htmlspecialchars($usuario['nome_completo']); ?></h1>
                        <?php if (!empty($usuario['nome_artistico'])): ?>
                            <div class="username">@<?php echo htmlspecialchars($usuario['nome_artistico']); ?></div>
                        <?php endif; ?>
                        
                        <div class="profile-meta">
                            <span>
                                <i class="fa-solid fa-location-dot"></i>
                                <?php echo htmlspecialchars($usuario['cidade'] . ', ' . $usuario['estado']); ?>
                            </span>
                            <?php if (!empty($usuario['anos_experiencia'])): ?>
                            <span>
                                <i class="fa-solid fa-clock"></i>
                                <?php echo $usuario['anos_experiencia']; ?> anos de experiência
                            </span>
                            <?php endif; ?>
                            <span>
                                <i class="fa-solid fa-calendar"></i>
                                Desde <?php echo date('m/Y', strtotime($usuario['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="editar_perfil.php" class="btn btn-primary">
                        <i class="fa-solid fa-pen"></i>
                        Editar Perfil
                    </a>
                    <a href="php/logout.php" class="btn btn-secondary">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Sair
                    </a>
                </div>
            </div>

            <?php if (!empty($usuario['area_atuacao'])): ?>
            <div class="tag-list">
                <?php 
                $areas = explode(', ', $usuario['area_atuacao']);
                foreach ($areas as $area): 
                ?>
                    <span class="tag"><?php echo htmlspecialchars($area); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- CONTEÚDO DO PERFIL -->
        <div class="perfil-content">
            <!-- SOBRE MIM -->
            <div class="perfil-card">
                <h3 class="card-title">
                    <i class="fa-solid fa-user"></i>
                    Sobre Mim
                </h3>
                <?php if (!empty($usuario['biografia'])): ?>
                    <p class="biografia"><?php echo nl2br(htmlspecialchars($usuario['biografia'])); ?></p>
                <?php else: ?>
                    <div class="empty-state">Nenhuma biografia adicionada ainda.</div>
                <?php endif; ?>
            </div>

            <!-- INFORMAÇÕES DE CONTATO -->
            <div class="perfil-card">
                <h3 class="card-title">
                    <i class="fa-solid fa-address-card"></i>
                    Informações de Contato
                </h3>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span><?php echo htmlspecialchars($usuario['email']); ?></span>
                </div>
                <?php if (!empty($usuario['telefone'])): ?>
                <div class="info-row">
                    <span class="info-label">Telefone:</span>
                    <span><?php echo htmlspecialchars($usuario['telefone']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Localização:</span>
                    <span>
                        <?php 
                        echo htmlspecialchars($usuario['cidade'] . ', ' . $usuario['estado']);
                        if (!empty($usuario['bairro'])) {
                            echo ' - ' . htmlspecialchars($usuario['bairro']);
                        }
                        ?>
                    </span>
                </div>
            </div>

            <!-- INSTRUMENTOS -->
            <div class="perfil-card">
                <h3 class="card-title">
                    <i class="fa-solid fa-guitar"></i>
                    Instrumentos
                </h3>
                <?php if (!empty($instrumentos)): ?>
                    <div class="tag-list">
                        <?php foreach ($instrumentos as $inst): ?>
                            <span class="tag <?php echo $inst['principal'] ? 'principal' : ''; ?>">
                                <?php echo htmlspecialchars($inst['nome_instrumento']); ?>
                                <?php if ($inst['principal']): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">Nenhum instrumento cadastrado.</div>
                <?php endif; ?>
            </div>

            <!-- GÊNEROS MUSICAIS -->
            <div class="perfil-card">
                <h3 class="card-title">
                    <i class="fa-solid fa-music"></i>
                    Gêneros Musicais
                </h3>
                <?php if (!empty($generos)): ?>
                    <div class="tag-list">
                        <?php foreach ($generos as $gen): ?>
                            <span class="tag"><?php echo htmlspecialchars($gen['nome_genero']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">Nenhum gênero cadastrado.</div>
                <?php endif; ?>
            </div>

            <!-- DISPONIBILIDADE -->
            <div class="perfil-card">
                <h3 class="card-title">
                    <i class="fa-solid fa-clock"></i>
                    Disponibilidade
                </h3>
                <?php if (!empty($disponibilidade)): ?>
                    <div class="tag-list">
                        <?php foreach ($disponibilidade as $disp): ?>
                            <span class="tag">
                                <?php 
                                $icone = '';
                                if ($disp === 'Manhã') $icone = '☀️';
                                elseif ($disp === 'Tarde') $icone = '🌤️';
                                elseif ($disp === 'Noite') $icone = '🌙';
                                echo $icone . ' ' . htmlspecialchars($disp); 
                                ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">Disponibilidade não definida.</div>
                <?php endif; ?>
            </div>

            <!-- LINK PARA VER OUTROS MÚSICOS -->
            <div class="perfil-card" style="grid-column: 1 / -1;">
                <h3 class="card-title">
                    <i class="fa-solid fa-users"></i>
                    Explore a Comunidade
                </h3>
                <a href="perfil_usuario.php" class="btn btn-primary" style="width: fit-content; margin: 0 auto;">
                    <i class="fa-solid fa-search"></i>
                    Encontrar Músicos
                </a>
            </div>
        </div>
    </div>
</body>
</html>
