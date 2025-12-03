CREATE DATABASE IF NOT EXISTS monopoly;
USE monopoly;

-- 1. ADMIN
CREATE TABLE IF NOT EXISTS Admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- 2. BANK
CREATE TABLE IF NOT EXISTS Bank (
    bank_id INT AUTO_INCREMENT PRIMARY KEY,
    total_funds BIGINT NOT NULL,
    mortgage_rate DECIMAL(5,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    backup_status VARCHAR(50)
);

-- 3. GAME
CREATE TABLE IF NOT EXISTS Game (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    start_time DATETIME NOT NULL,
    last_saved_time DATETIME,
    status ENUM('ongoing','completed') NOT NULL DEFAULT 'ongoing',
    current_turn INT,
    save_file_path VARCHAR(255)
);

-- 4. PLAYER
CREATE TABLE IF NOT EXISTS Player (
    player_id INT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    money INT NOT NULL,
    position INT NOT NULL,
    is_in_jail BOOLEAN DEFAULT FALSE,
    has_get_out_card BOOLEAN DEFAULT FALSE,
    current_game_id INT,
    FOREIGN KEY (current_game_id) REFERENCES Game(game_id)
);

-- 5. WALLET (1–1 with Player)
CREATE TABLE IF NOT EXISTS Wallet (
    player_id INT PRIMARY KEY,
    propertyWorthCash INT DEFAULT 0,
    number_of_properties INT DEFAULT 0,
    debt_to_players INT DEFAULT 0,
    debt_from_players INT DEFAULT 0,
    FOREIGN KEY (player_id) REFERENCES Player(player_id)
);

-- 6. PROPERTY
CREATE TABLE IF NOT EXISTS Property (
    property_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    rent INT NOT NULL,
    color_group VARCHAR(50),
    is_mortgaged BOOLEAN DEFAULT FALSE,
    owner_id INT NULL,
    FOREIGN KEY (owner_id) REFERENCES Player(player_id)
);

-- 7. BANK TRANSACTIONS
CREATE TABLE IF NOT EXISTS BankTransaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    bank_id INT DEFAULT 1,
    player_id INT NOT NULL,
    property_id INT NULL,
    type ENUM('purchase','sell','mortgage','loan') NOT NULL,
    amount INT NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (bank_id) REFERENCES Bank(bank_id),
    FOREIGN KEY (player_id) REFERENCES Player(player_id),
    FOREIGN KEY (property_id) REFERENCES Property(property_id)
);

-- 8. PERSONAL TRANSACTIONS
CREATE TABLE IF NOT EXISTS PersonalTransaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    from_player_id INT NOT NULL,
    to_player_id INT NOT NULL,
    reason VARCHAR(100),
    amount INT NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (from_player_id) REFERENCES Player(player_id),
    FOREIGN KEY (to_player_id) REFERENCES Player(player_id)
);

-- 9. BOARD TILE
CREATE TABLE IF NOT EXISTS BoardTile (
    tile_id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('property','chance','community','jail','go','tax','free_parking','go_to_jail') NOT NULL,
    property_id INT NULL,
    position_on_board INT NOT NULL,
    map_id INT,
    FOREIGN KEY (property_id) REFERENCES Property(property_id)
);

-- 10. CARD
CREATE TABLE IF NOT EXISTS Card (
    card_id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Chance','Community Chest') NOT NULL,
    description TEXT NOT NULL,
    effect_type ENUM('move','pay','receive','jail','get_out_card','other') NOT NULL,
    value INT
);

-- 11. DICE
CREATE TABLE IF NOT EXISTS Dice (
    dice_id INT AUTO_INCREMENT PRIMARY KEY,
    value INT NOT NULL
);

-- 12. LOG
CREATE TABLE IF NOT EXISTS Log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    description TEXT NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (game_id) REFERENCES Game(game_id)
);

-- 13. BACKUP
CREATE TABLE IF NOT EXISTS Backup (
    backup_id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    created_by INT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id)
);

-- 14. SAVE FILE
CREATE TABLE IF NOT EXISTS SaveFile (
    save_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    saved_time DATETIME NOT NULL,
    FOREIGN KEY (game_id) REFERENCES Game(game_id),
    FOREIGN KEY (player_id) REFERENCES Player(player_id)
);

-- 15. MAP
CREATE TABLE IF NOT EXISTS Map (
    map_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    layout_file VARCHAR(255),
    num_tiles INT
);

-- Missing Foreign Key (BoardTile → Map)
ALTER TABLE BoardTile
    ADD CONSTRAINT fk_boardtile_map
    FOREIGN KEY (map_id) REFERENCES Map(map_id);

-- 16. SETTINGS (1–1 with Game)
CREATE TABLE IF NOT EXISTS Settings (
    settings_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL UNIQUE,
    max_players INT NOT NULL,
    board_theme VARCHAR(100),
    dice_type VARCHAR(50),
    FOREIGN KEY (game_id) REFERENCES Game(game_id)
);
