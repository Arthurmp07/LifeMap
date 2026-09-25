-- Perfis (usuário, profissional, administrador), atendimento (vínculo profissional-usuário),
-- chat e chamadas de voz/vídeo. Pode ser executado mais de uma vez.
--
-- papel:         quem a pessoa é no site. Só o administrador cria profissionais.
-- ativo:         0 = conta desativada (não consegue entrar).
-- trocar_senha:  1 = a senha atual é provisória; a pessoa precisa trocá-la ao entrar.
-- gênero e data de nascimento passam a aceitar NULL: contas de profissional e administrador
-- não usam esses dados (o cadastro de usuário continua exigindo os dois).

ALTER TABLE `usuarios`
  MODIFY `genero` enum('feminino','masculino') DEFAULT NULL,
  MODIFY `data_nascimento` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `papel` enum('usuario','profissional','admin') NOT NULL DEFAULT 'usuario',
  ADD COLUMN IF NOT EXISTS `ativo` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `trocar_senha` tinyint(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `profissionais` (
  `usuario_id` int(11) NOT NULL,
  `especialidade` varchar(80) NOT NULL,
  `registro` varchar(40) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `profissionais_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Um registro por par (profissional, usuário): o convite vira atendimento ativo quando o usuário
-- aceita, e pode ser encerrado por qualquer um dos dois (ou pelo administrador).
-- ficha_vista_em: última vez que o profissional abriu os dados do usuário (o usuário vê essa data).
CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profissional_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status` enum('pendente','ativo','encerrado','recusado') NOT NULL DEFAULT 'pendente',
  `convidado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `respondido_em` datetime DEFAULT NULL,
  `encerrado_em` datetime DEFAULT NULL,
  `encerrado_por` int(11) DEFAULT NULL,
  `ficha_vista_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `par` (`profissional_id`,`usuario_id`),
  KEY `usuario_status` (`usuario_id`,`status`),
  CONSTRAINT `atendimentos_ibfk_1` FOREIGN KEY (`profissional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `atendimentos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- tipo 'sistema' = avisos automáticos no chat (chamada perdida, duração da chamada...).
CREATE TABLE IF NOT EXISTS `mensagens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `atendimento_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `tipo` enum('texto','sistema') NOT NULL DEFAULT 'texto',
  `texto` varchar(2000) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `lida_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversa` (`atendimento_id`,`id`),
  KEY `remetente_tempo` (`remetente_id`,`criado_em`),
  CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Uma chamada por vez em cada atendimento. Nada de áudio/vídeo passa pelo servidor:
-- ele só guarda os "sinais" (ofertas/respostas WebRTC e candidatos ICE) para os dois lados se acharem.
-- visto_iniciador_em / visto_outro_em: última vez que cada lado consultou a chamada (batimento);
-- se um lado some, o servidor encerra a chamada.
CREATE TABLE IF NOT EXISTS `chamadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `atendimento_id` int(11) NOT NULL,
  `iniciador_id` int(11) NOT NULL,
  `com_video` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('tocando','em_andamento','encerrada','recusada','perdida','cancelada') NOT NULL DEFAULT 'tocando',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atendida_em` datetime DEFAULT NULL,
  `encerrada_em` datetime DEFAULT NULL,
  `visto_iniciador_em` datetime DEFAULT NULL,
  `visto_outro_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `atendimento_status` (`atendimento_id`,`status`),
  CONSTRAINT `chamadas_ibfk_1` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chamadas_ibfk_2` FOREIGN KEY (`iniciador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Para bancos criados com a versão anterior desta migração (sem os batimentos).
ALTER TABLE `chamadas`
  ADD COLUMN IF NOT EXISTS `visto_iniciador_em` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `visto_outro_em` datetime DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `sinais_chamada` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `chamada_id` int(11) NOT NULL,
  `de_id` int(11) NOT NULL,
  `tipo` enum('offer','answer','ice') NOT NULL,
  `dados` text NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chamada_sinais` (`chamada_id`,`id`),
  CONSTRAINT `sinais_chamada_ibfk_1` FOREIGN KEY (`chamada_id`) REFERENCES `chamadas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
