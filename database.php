<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}certificate",
        "create table {$CFG->dbprefix}certificate (
    cert_id       INTEGER NOT NULL AUTO_INCREMENT,
    context_id    INTEGER NOT NULL,
    link_id       INTEGER NOT NULL,
    user_id       INTEGER NOT NULL,
    title         VARCHAR(255) NULL,
    issued_by     VARCHAR(255) NULL,
    DETAILS       TEXT NULL,
    modified      datetime NOT NULL,
    
    PRIMARY KEY(cert_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8"),
    array( "{$CFG->dbprefix}cert_award",
        "create table {$CFG->dbprefix}cert_award (
    award_id        INTEGER NOT NULL AUTO_INCREMENT,
    cert_id         INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    date_awarded    datetime NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}ca_ibfk_1`
        FOREIGN KEY (`cert_id`)
        REFERENCES `{$CFG->dbprefix}certificate` (`cert_id`)
        ON DELETE CASCADE,

    PRIMARY KEY(award_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);