<?php
$response = ["message" => "There was a problem completing your purchase"];
http_response_code(400);
error_log("req: " . var_export($_POST, true));
if (isset($_POST["item_id"]) && isset($_POST["quantity"])) {
  error_log("processing add to cart");
    require_once(__DIR__ . "/../../../lib/functions.php");
    session_start();
    $user_id = get_user_id();
    $item_id = (int)se($_POST, "item_id", 0, false);
    $quantity = (int)se($_POST, "quantity", 0, false);
    $isValid = true;
    $errors = [];
    if ($user_id <= 0) {
        //invald user
        array_push($errors, "Invalid user");
error_log("invalid user $user_id");
        $isValid = false;
    }
    if ($quantity <= 0) {
        //invalid quantity
        array_push($errors, "Invalid quantity");
    error_log("invalid quantity $quantity");
        $isValid = false;
    }
    if($isValid){
        $db = getDB();
        $stmt = $db->prepare("SELECT name, unit_price FROM Products where id = :id");
        $name = "";
        try {
            $stmt->execute([":id" => $item_id]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $name = se($r, "name", "", false);
                $price = se($r, "unit_price", "", false);
                error_log("Got item $name");
            }
        } catch (PDOException $e) {
            error_log("Error getting name of $item_id: " . var_export($e->errorInfo, true));
            $isValid = false;
        }
    }
    if ($isValid) {
error_log("before add to cart");
        add_to_cart($item_id, $user_id, $quantity, $price);
error_log("after add to cart");
        http_response_code(200);
        $response["message"] = "Added $quantity of $name to cart";
        //success
    } else {
        $response["message"] = join("<br>", $errors);
    }
}
error_log("sending response: " . var_export($response, true));
echo json_encode($response);