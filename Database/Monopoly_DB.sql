-- User Accounts
CREATE TABLE IF NOT EXISTS User(
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    useremail VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    wins INT DEFAULT 0,
    losses INT DEFAULT 0,
    user_created DATE
);

-- ADMIN
CREATE TABLE IF NOT EXISTS Admin (
    user_id INT PRIMARY KEY,
    admin_id VARCHAR(50) UNIQUE,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Game Info
CREATE TABLE IF NOT EXISTS Game (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,              
    start_time DATETIME NOT NULL,
    last_saved_time DATETIME,
    passing_GO INT NOT NULL,
    status ENUM('ongoing','completed') NOT NULL DEFAULT 'ongoing',
    FOREIGN KEY (user_id) REFERENCES User(user_id)
);

--  PLAYER
CREATE TABLE IF NOT EXISTS Player (
    player_id INT AUTO_INCREMENT PRIMARY KEY,
    player_name VARCHAR(300),
    money INT NOT NULL,
    position INT NOT NULL,
    is_in_jail BOOLEAN DEFAULT FALSE,
    has_get_out_card BOOLEAN DEFAULT FALSE,
    current_game_id INT,
    FOREIGN KEY (current_game_id) REFERENCES Game(game_id)
);

-- Bank
CREATE TABLE IF NOT EXISTS Bank (
    bank_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    total_funds BIGINT NOT NULL,
    FOREIGN KEY (game_id) REFERENCES Game(game_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- WALLET
CREATE TABLE IF NOT EXISTS Wallet (
    player_id INT PRIMARY KEY,
    propertyWorthCash INT DEFAULT 0,
    number_of_properties INT DEFAULT 0,
    debt_to_players INT DEFAULT 0,
    debt_from_players INT DEFAULT 0,
    FOREIGN KEY (player_id) REFERENCES Player(player_id)
);

-- PROPERTY
CREATE TABLE IF NOT EXISTS Property (
    property_id INT AUTO_INCREMENT PRIMARY KEY,
    price INT NOT NULL,
    rent INT NOT NULL,
    house_count INT DEFAULT 0,
    hotel_count INT DEFAULT 0,
    is_mortgaged BOOLEAN DEFAULT FALSE,
    owner_id INT NULL,
    FOREIGN KEY (owner_id) REFERENCES Player(player_id)
);

-- BANK TRANSACTIONS
CREATE TABLE IF NOT EXISTS BankTransaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    bank_id INT NOT NULL,
    player_id INT NOT NULL,
    property_id INT NULL,
    type ENUM('purchase','sell','mortgage','loan') NOT NULL,
    amount INT NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (bank_id) REFERENCES Bank(bank_id),
    FOREIGN KEY (player_id) REFERENCES Player(player_id),
    FOREIGN KEY (property_id) REFERENCES Property(property_id)
);

-- PERSONAL TRANSACTIONS
CREATE TABLE IF NOT EXISTS PersonalTransaction (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    from_player_id INT NOT NULL,
    to_player_id INT NOT NULL,
    amount INT NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (from_player_id) REFERENCES Player(player_id),
    FOREIGN KEY (to_player_id) REFERENCES Player(player_id)
);

-- LOG
CREATE TABLE IF NOT EXISTS Log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    description TEXT NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (game_id) REFERENCES Game(game_id)
);

-- CREATE TABLE IF NOT EXISTS BoardTile (
--     tile_id INT AUTO_INCREMENT PRIMARY KEY,
--     position INT NOT NULL UNIQUE,   -- 0–39
--     type ENUM('property','chance','community','tax','go','jail','free_parking','go_to_jail') NOT NULL,
--     property_id INT NULL,
--     FOREIGN KEY (property_id) REFERENCES Property(property_id)
-- );