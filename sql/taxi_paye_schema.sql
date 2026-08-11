CREATE DATABASE IF NOT EXISTS taxi_paye CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taxi_paye;

CREATE TABLE IF NOT EXISTS chauffeur (
    id INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entree_journaliere (
    id INT NOT NULL AUTO_INCREMENT,
    chauffeur_id INT NOT NULL,
    date_entree DATE NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chauffeur_date (chauffeur_id, date_entree),
    CONSTRAINT fk_entree_chauffeur FOREIGN KEY (chauffeur_id) REFERENCES chauffeur(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    CONSTRAINT fk_depense_chauffeur FOREIGN KEY (chauffeur_id) REFERENCES chauffeur(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
