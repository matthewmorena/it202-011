CREATE TABLE IF NOT EXISTS Products(
    id int AUTO_INCREMENT PRIMARY  KEY,
    name varchar(60) UNIQUE, -- alternatively you'd have a SKU that's unique
    description text,
    stock int DEFAULT  0,
    unit_price int DEFAULT  99999,
    visibility int DEFAULT 1,
    category varchar(20),
    image text, -- this col type can't have a default value
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
)