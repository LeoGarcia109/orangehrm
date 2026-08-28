-- ============================================================================
-- OrangeHRM BR - Migracao 007: segredo de assinatura dos registros de ponto
-- ============================================================================
--
-- O hash de inviolabilidade (Portaria 671/2021) so vale enquanto depende de um
-- segredo. Sem ele o hash e um digest de colunas publicas: quem alterou o
-- registro recalcula o valor e a adulteracao fica invisivel.
--
-- Ate esta migracao a chave `attendance.br.signature_secret` NAO existia, e o
-- codigo caia em uma chave derivada do hostname. Esta migracao cria a chave
-- com bytes aleatorios do proprio MySQL (RANDOM_BYTES usa o RNG do OpenSSL).
--
-- ATENCAO
--   1. Guarde este valor em backup junto com o banco. Trocar o segredo
--      invalida o hash de TODOS os registros ja assinados - eles passam a
--      aparecer como VIOLATED na verificacao.
--   2. A migracao e idempotente: se a chave ja existir, nada e alterado.
--      Nunca rode um UPDATE aqui.
--   3. Depois de aplicar, assine os registros ja existentes:
--      POST /api/v2/attendance/br/signature/verify {"fromDate","toDate"}
--
-- Requer MySQL 8.0.17+ (RANDOM_BYTES).
-- ----------------------------------------------------------------------------

INSERT INTO `hs_hr_config` (`name`, `value`)
SELECT 'attendance.br.signature_secret', SHA2(RANDOM_BYTES(64), 256)
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `name` FROM `hs_hr_config`) AS c
    WHERE c.`name` = 'attendance.br.signature_secret'
);

-- Conferencia: deve retornar 1 linha, com 64 caracteres hex.
-- SELECT `name`, LENGTH(`value`) FROM `hs_hr_config`
--  WHERE `name` = 'attendance.br.signature_secret';
