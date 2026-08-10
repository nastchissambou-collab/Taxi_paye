CREATE DATABASE IF NOT EXISTS taxi_paye CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taxi_paye;

-- =========================================================
-- 1. Table chauffeur
-- =========================================================
CREATE TABLE IF NOT EXISTS chauffeur (
    id INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 2. Entrées journalières (recettes du taxi)
--    Exemple : total encaissé pour une journée
-- =========================================================
CREATE TABLE IF NOT EXISTS entree_journaliere (
    id INT NOT NULL AUTO_INCREMENT,
    chauffeur_id INT NOT NULL,
    date_entree DATE NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chauffeur_date (chauffeur_id, date_entree),
    CONSTRAINT fk_entree_chauffeur
        FOREIGN KEY (chauffeur_id) REFERENCES chauffeur(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 3. Dépenses effectuées, avec motif optionnel
-- =========================================================
CREATE TABLE IF NOT EXISTS depense (
    id INT NOT NULL AUTO_INCREMENT,
    chauffeur_id INT NOT NULL,
    date_depense DATE NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    motif VARCHAR(150) NULL,
    commentaire TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_depense_chauffeur_date (chauffeur_id, date_depense),
    CONSTRAINT fk_depense_chauffeur
        FOREIGN KEY (chauffeur_id) REFERENCES chauffeur(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 4. Périodes de paie
--    La paie est payée au 5 du mois, sur la période du 5 du mois
--    précédent au 4 du mois courant.
-- =========================================================
CREATE TABLE IF NOT EXISTS periode_paie (
    id INT NOT NULL AUTO_INCREMENT,
    annee INT NOT NULL,
    mois INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    date_paiement DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_periode (annee, mois),
    UNIQUE KEY uk_date_paiement (date_paiement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 5. Vue de synthèse par période de paie
-- =========================================================
CREATE OR REPLACE VIEW v_recap_periode AS
SELECT
    c.id AS chauffeur_id,
    c.prenom AS chauffeur_prenom,
    pp.annee,
    pp.mois,
    pp.date_debut,
    pp.date_fin,
    pp.date_paiement,
    COALESCE(SUM(e.montant), 0) AS total_entrees,
    COALESCE(SUM(d.montant), 0) AS total_depenses,
    COALESCE(SUM(e.montant), 0) - COALESCE(SUM(d.montant), 0) AS total_net,
    ROUND(COALESCE(SUM(e.montant), 0) * 0.25, 2) AS paie_du_chauffeur
FROM chauffeur c
CROSS JOIN periode_paie pp
LEFT JOIN entree_journaliere e
    ON e.chauffeur_id = c.id
   AND e.date_entree BETWEEN pp.date_debut AND pp.date_fin
LEFT JOIN depense d
    ON d.chauffeur_id = c.id
   AND d.date_depense BETWEEN pp.date_debut AND pp.date_fin
GROUP BY c.id, c.prenom, pp.id, pp.annee, pp.mois, pp.date_debut, pp.date_fin, pp.date_paiement;

-- =========================================================
-- 6. Procédure pour recalculer une période de paie
--    25% calculé uniquement sur les entrées
-- =========================================================
DELIMITER //
CREATE PROCEDURE sp_calcul_paye_periode(
    IN p_chauffeur_id INT,
    IN p_annee INT,
    IN p_mois INT
)
BEGIN
    DECLARE v_date_debut DATE;
    DECLARE v_date_fin DATE;
    DECLARE v_date_paiement DATE;
    DECLARE v_total_entrees DECIMAL(10,2) DEFAULT 0;
    DECLARE v_total_depenses DECIMAL(10,2) DEFAULT 0;
    DECLARE v_total_net DECIMAL(10,2) DEFAULT 0;
    DECLARE v_paye_chauffeur DECIMAL(10,2) DEFAULT 0;

    IF p_mois = 1 THEN
        SET v_date_debut = DATE(CONCAT(p_annee - 1, '-12-05'));
        SET v_date_fin = DATE(CONCAT(p_annee, '-01-04'));
    ELSE
        SET v_date_debut = DATE(CONCAT(p_annee, '-', LPAD(p_mois - 1, 2, '0'), '-05'));
        SET v_date_fin = DATE(CONCAT(p_annee, '-', LPAD(p_mois, 2, '0'), '-04'));
    END IF;

    SET v_date_paiement = DATE(CONCAT(p_annee, '-', LPAD(p_mois, 2, '0'), '-05'));

    SELECT COALESCE(SUM(montant), 0)
    INTO v_total_entrees
    FROM entree_journaliere
    WHERE chauffeur_id = p_chauffeur_id
      AND date_entree BETWEEN v_date_debut AND v_date_fin;

    SELECT COALESCE(SUM(montant), 0)
    INTO v_total_depenses
    FROM depense
    WHERE chauffeur_id = p_chauffeur_id
      AND date_depense BETWEEN v_date_debut AND v_date_fin;

    SET v_total_net = v_total_entrees - v_total_depenses;
    SET v_paye_chauffeur = ROUND(v_total_entrees * 0.25, 2);

    INSERT INTO periode_paie (annee, mois, date_debut, date_fin, date_paiement)
    VALUES (p_annee, p_mois, v_date_debut, v_date_fin, v_date_paiement)
    ON DUPLICATE KEY UPDATE
        date_debut = v_date_debut,
        date_fin = v_date_fin,
        date_paiement = v_date_paiement;
END //
DELIMITER ;

-- =========================================================
-- 7. Exemples d'insertion (facultatif)
-- =========================================================
INSERT INTO chauffeur (nom, prenom, telephone)
VALUES ('Diallo', 'Moussa', '771234567')
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

-- Exemple : période de paie
INSERT INTO periode_paie (annee, mois, date_debut, date_fin, date_paiement)
VALUES
(2026, 8, '2026-07-05', '2026-08-04', '2026-08-05')
ON DUPLICATE KEY UPDATE date_debut = VALUES(date_debut), date_fin = VALUES(date_fin), date_paiement = VALUES(date_paiement);

-- Exemple : entrées
INSERT INTO entree_journaliere (chauffeur_id, date_entree, montant)
VALUES
(1, '2026-07-10', 3000.00),
(1, '2026-07-20', 2800.00),
(1, '2026-08-03', 3500.00);

-- Exemple : dépenses
INSERT INTO depense (chauffeur_id, date_depense, montant, motif, commentaire)
VALUES
(1, '2026-07-11', 250.00, 'Essence', 'Carburant du matin'),
(1, '2026-08-01', 180.00, 'Maintenance', 'Petite réparation');

-- Exemple d'appel de la procédure
CALL sp_calcul_paye_periode(1, 2026, 8);

-- =========================================================
-- 8. Requêtes utiles pour le suivi
-- =========================================================
-- Total mensuel des entrées pour un chauffeur donné :
-- SELECT IFNULL(SUM(montant), 0) FROM entree_journaliere WHERE chauffeur_id = 1 AND MONTH(date_entree)=8 AND YEAR(date_entree)=2026;

-- Total mensuel des dépenses :
-- SELECT IFNULL(SUM(montant), 0) FROM depense WHERE chauffeur_id = 1 AND MONTH(date_depense)=8 AND YEAR(date_depense)=2026;

-- Résultat final du mois :
-- SELECT * FROM paie_mensuelle WHERE chauffeur_id = 1 AND annee = 2026 AND mois = 8;
