-- Avaliações físicas feitas pela câmera: a foto fica em storage/avaliacoes/ (fora do acesso público)
-- e aqui ficam as medidas. Pode ser executado mais de uma vez.
--
-- Convenção dos sinais (lados da PESSOA avaliada):
--   inclinacao_ombros / inclinacao_quadril: positivo = lado direito mais alto (graus)
--   desvio_cabeca: positivo = cabeça deslocada para a direita (% da largura dos ombros)
--   inclinacao_tronco: positivo = tronco inclinado para a direita (graus)

CREATE TABLE IF NOT EXISTS `avaliacoes_fisicas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `arquivo` char(36) NOT NULL,
  `razao_ombros_quadril` decimal(5,3) NOT NULL,
  `inclinacao_ombros` decimal(5,2) NOT NULL,
  `inclinacao_quadril` decimal(5,2) NOT NULL,
  `desvio_cabeca` decimal(5,2) NOT NULL,
  `inclinacao_tronco` decimal(5,2) NOT NULL,
  `corpo_inteiro` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `arquivo` (`arquivo`),
  KEY `usuario_data` (`usuario_id`,`criado_em`),
  CONSTRAINT `avaliacoes_fisicas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
