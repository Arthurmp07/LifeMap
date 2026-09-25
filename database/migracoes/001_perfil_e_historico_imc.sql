-- Fase 2: perfil do usuário e histórico de IMC.
-- Para bancos criados com a versão anterior de academia.sql.
-- (Usa IF NOT EXISTS, do MariaDB: pode ser executado mais de uma vez.)

ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `altura` decimal(3,2) DEFAULT NULL AFTER `telefone`,
  ADD COLUMN IF NOT EXISTS `objetivo` enum('perder_peso','ganhar_peso','ganhar_musculo','manter_saude') DEFAULT NULL AFTER `altura`,
  ADD COLUMN IF NOT EXISTS `problema_saude` varchar(255) DEFAULT NULL AFTER `objetivo`;

ALTER TABLE `imc`
  ADD COLUMN IF NOT EXISTS `criado_em` timestamp NOT NULL DEFAULT current_timestamp() AFTER `resultado_imc`,
  ADD INDEX IF NOT EXISTS `usuario_data` (`usuario_id`, `criado_em`);
