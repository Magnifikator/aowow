DROP TABLE IF EXISTS `aowow_profiler_sync`;
CREATE TABLE `aowow_profiler_sync` (
    `realm` TINYINT(3) UNSIGNED NOT NULL,
    `realmGUID` INT(10) UNSIGNED NOT NULL,
    `type` SMALLINT(5) UNSIGNED NOT NULL,
    `typeId` MEDIUMINT(8) UNSIGNED NOT NULL,
    `requestTime` INT(10) UNSIGNED NOT NULL,
    `status` TINYINT(3) UNSIGNED NOT NULL,
    `errorCode` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
    UNIQUE INDEX `realm_realmGUID_type_typeId` (`realm`, `realmGUID`, `type`, `typeId`)
) COLLATE='utf8_general_ci' ENGINE=InnoDB;

DROP TABLE IF EXISTS `aowow_profiler_profiles`;
CREATE TABLE `aowow_profiler_profiles` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `realm` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
    `realmGUID` INT(11) UNSIGNED NULL DEFAULT NULL,
    `cuFlags` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `user` INT(11) UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(12) NOT NULL,
    `race` TINYINT(3) UNSIGNED NOT NULL,
    `class` TINYINT(3) UNSIGNED NOT NULL,
    `level` TINYINT(3) UNSIGNED NOT NULL,
    `gender` TINYINT(3) UNSIGNED NOT NULL,
    `skincolor` TINYINT(3) UNSIGNED NOT NULL,
    `hairstyle` TINYINT(3) UNSIGNED NOT NULL,
    `haircolor` TINYINT(3) UNSIGNED NOT NULL,
    `facetype` TINYINT(3) UNSIGNED NOT NULL,
    `features` TINYINT(3) UNSIGNED NOT NULL,
    `nomodelMask` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `title` TINYINT(3) UNSIGNED NOT NULL,
    `description` TEXT NULL,
    `playedtime` INT(11) UNSIGNED NOT NULL,
    `lastupdated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `spec1` TEXT NOT NULL,
    `spec2` TEXT NOT NULL,
    `activespec` TINYINT(3) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `realm_realmGUID` (`realm`, `realmGUID`)
) COLLATE='utf8_general_ci' ENGINE=InnoDB;

DROP TABLE IF EXISTS `aowow_profiler_items`;
CREATE TABLE `aowow_profiler_items` (
    `id` INT(11) UNSIGNED NULL DEFAULT NULL,
    `slot` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
    `item` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
    `subItem` SMALLINT(6) NULL DEFAULT NULL,
    `permEnchant` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
    `tempEnchant` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
    `extraSocket` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
    `gem1` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
    `gem2` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
    `gem3` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL,
    `gem4` MEDIUMINT(8) UNSIGNED NULL DEFAULT NULL COMMENT 'not used',
    UNIQUE INDEX `id_slot` (`id`, `slot`),
    INDEX `id` (`id`),
    CONSTRAINT `FK_pr_items` FOREIGN KEY (`id`) REFERENCES `aowow_profiler_profiles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) COLLATE='utf8_general_ci' ENGINE=InnoDB;

DROP TABLE IF EXISTS `aowow_profiler_completion`;
CREATE TABLE `aowow_profiler_completion` (
    `id` INT(11) UNSIGNED NOT NULL,
    `type` SMALLINT(6) UNSIGNED NOT NULL,
    `typeId` MEDIUMINT(9) NOT NULL,
    `cur` INT(11) NULL DEFAULT NULL,
    `max` INT(11) NULL DEFAULT NULL,
    INDEX `id` (`id`),
    CONSTRAINT `FK_pr_completion` FOREIGN KEY (`id`) REFERENCES `aowow_profiler_profiles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) COLLATE='utf8_general_ci' ENGINE=InnoDB;

DROP TABLE IF EXISTS `aowow_profiler_achievement_progress`;
CREATE TABLE `aowow_profiler_achievement_progress` (
    `id` INT(11) NULL DEFAULT NULL,
    `achievement` SMALLINT(6) NULL DEFAULT NULL,
    `cirterium` SMALLINT(6) NULL DEFAULT NULL,
    INDEX `id` (`id`)
) COLLATE='utf8_general_ci' ENGINE=InnoDB;
