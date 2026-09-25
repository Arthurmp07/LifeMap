-- Rotina (agenda) e registro de humor por dia.
-- Para bancos criados antes desta versão. Pode ser executado mais de uma vez.

CREATE TABLE IF NOT EXISTS `eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `categoria` enum('estudo','trabalho','treino','refeicao','sono','lazer','geral') NOT NULL DEFAULT 'geral',
  `inicio` datetime NOT NULL,
  `fim` datetime NOT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `serie_id` char(16) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_periodo` (`usuario_id`,`inicio`,`fim`),
  KEY `serie_id` (`serie_id`),
  CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eventos_periodo` CHECK (`fim` > `inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `humor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `nivel` tinyint(4) NOT NULL,
  `nota` varchar(200) DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_data` (`usuario_id`,`data`),
  CONSTRAINT `humor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `humor_nivel` CHECK (`nivel` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
