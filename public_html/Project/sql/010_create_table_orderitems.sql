CREATE TABLE IF NOT EXISTS OrderItems(
    id int AUTO_INCREMENT PRIMARY KEY,
    order_id int,
    product_id int,
    quantity int,
    user_id int,
    unit_price int,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (order_id) REFERENCES Orders(id),
    FOREIGN KEY (product_id) REFERENCES Products(id)
)