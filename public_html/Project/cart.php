<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You are not logged in!", "warning");
    redirect("$BASE_PATH" . "login.php");
}

$results = [];
$user_id = get_user_id();
$db = getDB();

if (isset($_POST['update'])) {
    $quant = $_POST['desired_quantity'];
    $product_id = $_POST['product_id'];
    if ($quant > 0) {
        $stmt = $db->prepare("UPDATE Cart SET desired_quantity = :q WHERE user_id = :uid AND product_id = :iid");
        try {
            $stmt->execute([":q" => $quant, "uid" => $user_id, "iid" => $product_id]);
            $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($r) {
                $results = $r;
            }
        } catch (PDOException $e) {
            //flash("<pre>" . var_export($e, true) . "</pre>");
            flash("We had some problems processing your request, please try again.", "danger");
        }
    }
    else {
        $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = :uid AND product_id = :iid");
        try {
            $stmt->execute(["uid" => $user_id, "iid" => $product_id]);
            $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($r) {
                $results = $r;
            }
        } catch (PDOException $e) {
            //flash("<pre>" . var_export($e, true) . "</pre>");
            flash("We had some problems processing your request, please try again.", "danger");
        }
    }
}
if (isset($_POST['remove'])) {
    $product_id = $_POST['product_id'];
    $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = :uid AND product_id = :iid");
    try {
        $stmt->execute(["uid" => $user_id, "iid" => $product_id]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        //flash("<pre>" . var_export($e, true) . "</pre>");
        flash("We had some problems processing your request, please try again.", "danger");
    }
}
if (isset($_POST['clear'])) {
    $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = :uid");
    try {
        $stmt->execute(["uid" => $user_id]);
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        //flash("<pre>" . var_export($e, true) . "</pre>");
        flash("We had some problems processing your request, please try again.", "danger");
    }
}

$stmt = $db->prepare("SELECT Products.id, Products.name, Products.description, Products.stock, Products.unit_price, Cart.desired_quantity, Cart.unit_cost FROM Cart LEFT JOIN Products ON Cart.product_id = Products.id WHERE user_id = :name;");
try {
    $stmt->execute([":name" => $user_id]);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    //flash("<pre>" . var_export($e, true) . "</pre>");
    flash("We had some problems processing your request, please try again.", "danger");
}
$total_cost = 0;
foreach ($results as $record) {
    $total_cost += $record['unit_price'] * $record['desired_quantity'];
}

foreach ($results as $record) {
    if ($record['unit_price'] != $record['unit_cost']) {
        $stmt = $db->prepare("UPDATE Cart SET unit_cost = :price WHERE product_id = :pid");
        try {
            $stmt->execute([":price" => $record['unit_price'], ":pid" => $record["id"]]);
        } catch (PDOException $e) {
            //flash("<pre>" . var_export($e, true) . "</pre>");
            flash("We had some problems processing your request, please try again.", "danger");
        }
    }
}

if (isset($_POST['order'])) {
    $order = [];
    $_POST['name'] .= ", " . $_POST['address'] . ", " . $_POST['appt'] . ", " . $_POST['city'] . ", " . $_POST['state'] . ", " . $_POST['country'] . ", " . $_POST['zip'];
    
    $order['user_id'] = get_user_id();
    $order['total_price'] = $total_cost;
    $order['address'] = $_POST['name'];
    $order['payment_method'] = $_POST['payment'];

    $is_valid = true;

    foreach ($results as $record) {
        if ((int) $record['desired_quantity'] > (int) $record['stock']) {
            $is_valid = false;
            flash("There is not enough stock of " . $record['name'] . " for your order. Please limit your order to " . $record['stock'] . " of this item.", "warning");
        }
    }
    
    if ($is_valid) {
        $id = save_data("Orders", $order);
        if ($id > 0) {
            flash("Your order $id has been placed successfully", "success");
        }
        $stmt = $db->prepare("INSERT INTO OrderItems(product_id, user_id, quantity, unit_price, order_id)
        SELECT product_id, user_id, desired_quantity, unit_cost, :order_id FROM Cart where user_id = :uid");
        try {
            $stmt->execute([":uid" => $user_id, ":order_id" => $id]);
        } catch (PDOException $e) {
            //flash("<pre>" . var_export($e, true) . "</pre>");
            flash("We had some problems processing your request, please try again.", "danger");
        }

        foreach ($results as $record) {
            $new_stock = $record['stock'] - $record['desired_quantity'];
            $stmt = $db->prepare("UPDATE Products SET stock = :stock WHERE id = :pid");
            try {
                $stmt->execute([":stock" => $new_stock, ":pid" => $record['id']]);
            } catch (PDOException $e) {
                //flash("<pre>" . var_export($e, true) . "</pre>");
                flash("We had some problems processing your request, please try again.", "danger");
            }
        }

        $stmt = $db->prepare("DELETE FROM Cart WHERE user_id = :uid");
        try {
            $stmt->execute([":uid" => $user_id]);
        } catch (PDOException $e) {
            //flash("<pre>" . var_export($e, true) . "</pre>");
            flash("We had some problems processing your request, please try again.", "danger");
        }

        die(header("Location: order_confirmation.php"));
    }
}
?>
<div class="container-fluid">
    <h1>Your Cart:</h1>
    <?php if (count($results) == 0) : ?>
        <p>Your cart is empty</p>
    <?php else : ?>
        <table class="table text-dark">
            <?php foreach ($results as $index => $record) : ?>
                <?php if ($index == 0) : ?>
                    <thead>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Stock</th>
                        <th>Unit Price</th>
                        <th>Desired Quantity</th>
                        <th>Subtotal</th>
                        <th>Actions</th>
                    </thead>
                <?php endif; ?>
                <tr>
                <?php foreach($record as $column => $value):?>
                    <?php if($column == "desired_quantity"):?>
                        <td width=15%><form method="POST">
                        <div class="input-group">
                            <input class="form-control" type="number" id="desired_quantity" name="desired_quantity" min="1" value="<?php se($value);?>"/>
                            <input type="hidden" name="product_id" value="<?php echo $record['id'];?>"/>
                            <input type="submit" name="update" value="Update" class="btn btn-dark"/>
                        </div></form></td>
                    <?php elseif ($column == "id" || $column == "unit_cost") : ?>
                    <?php elseif ($column == "unit_price") : ?>    
                        <td>$<?php se($value);?></td>
                    <?php else:?>
                        <td><?php se($value);?></td>
                    <?php endif?>
                <?php endforeach;?>
                    <td> <?php echo "$" . $record['unit_price'] * $record['desired_quantity']; ?> </td>
                    <td><form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $record['id'];?>"/>
                        <input type="submit" name="remove" value="Remove" class="btn btn-dark"/>
                    </form></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><?php echo "$" . $total_cost ?></td>
                <td></td>
            </tr>
        </table>
        <form method="POST">
            <input type="submit" name="clear" value="Clear Cart" class="btn btn-dark"/>
        </form>
        <form class="col-md-6 align-items-center" method="POST">
            <div class="row">
                <div class="input-group">
                    <div class="input-group-text">Name</div>
                    <input class="form-control" name="name" value="" />
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">Address</div>
                        <input class="form-control" name="address" value="" />
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">Apartment/Suite</div>
                        <input class="form-control" name="appt" value="" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">City</div>
                        <input class="form-control" name="city" value="" />
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">State/Prov</div>
                        <input class="form-control" name="state" value="" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">Country</div>
                        <input class="form-control" name="country" value="" />
                    </div>
                </div>
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">Zip/Postal Code</div>
                        <input class="form-control" name="zip" value="" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">Payment Method</div>
                        <input class="form-control" name="payment" value="" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="input-group">
                        <div class="input-group-text">Enter Value</div>
                        <input class="form-control" name="value" value="" />
                    </div>
                </div>
            </div>
            <button class="btn btn-dark" type="submit" name="order" value="submit">Place Order</button>
        </form>
    <?php endif; ?>
</div>
<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../partials/footer.php");
?>