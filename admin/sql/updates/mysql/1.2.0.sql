-- 1.2.0 : lien vers la société (table externe `societes`, lecture seule)
-- Mise à jour depuis 1.0.0 uniquement. Depuis une version d'essai 1.1.0 ou 1.2.x, faire une réinstallation propre.
ALTER TABLE `#__challenges`
  ADD COLUMN `societe_id` INT UNSIGNED NULL AFTER `description`,
  ADD KEY `idx_societe` (`societe_id`);
