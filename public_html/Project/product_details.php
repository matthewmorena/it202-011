<?php 

require(__DIR__ . "/../../partials/nav.php");

$results = [];
$db = getDB();

$prod = $_POST['product'];
$stmt = $db->prepare("SELECT id, name, description, unit_price, stock, image FROM Products WHERE id = $prod");
try {
    $stmt->execute();
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    flash("<pre>" . var_export($e, true) . "</pre>");
}

$ratings = [];
$stmt = $db->prepare("SELECT Ratings.id, Ratings.user_id, rating, comment, Ratings.created, username FROM Ratings LEFT JOIN Users ON Ratings.user_id = Users.id WHERE product_id = $prod");
try {
    $stmt->execute();
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $ratings = $r;
    }
} catch (PDOException $e) {
    flash("<pre>" . var_export($e, true) . "</pre>");
}
$count = 0;
$total = 0;
foreach ($ratings as $rate) {
    $count += 1;
    $total += $rate['rating'];
}
$avg_rating = $total/$count;

if (isset($_POST['review'])) {
    $params = [":pid" => $prod, ":uid" => get_user_id(), ":rating" => $_POST['rating'], ":comment" => $_POST['comment']];
    $stmt = $db->prepare("INSERT INTO Ratings(product_id, user_id, rating, comment) VALUES (:pid, :uid, :rating, :comment);");
    try {
        $stmt->execute($params);
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }

    $stmt = $db->prepare("SELECT Ratings.id, Ratings.user_id, rating, comment, Ratings.created, username FROM Ratings LEFT JOIN Users ON Ratings.user_id = Users.id WHERE product_id = $prod");
    try {
        $stmt->execute();
        $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($r) {
            $ratings = $r;
        }
    } catch (PDOException $e) {
        flash("<pre>" . var_export($e, true) . "</pre>");
    }
    flash("Thanks for your feedback!", "success");
}
?>
<script>
    function cart(item, quantity) {
        let data = new FormData();
        data.append("item_id", item);
        data.append("quantity", quantity);
        fetch("api/cart_item.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: new URLSearchParams(Object.fromEntries(data))
            })
            .then(response => response.json())
            .then(data => {
                console.log('Success:', data);
                flash(data.message, "success");
            })
            .catch((error) => {
                console.error('Error:', error);
            });
    }
</script>
<style>
    .txt-center {
        text-align: center;
    }
    .hide {
        display: none;
    }
    .clear {
        float: none;
        clear: both;
    }
    .rating {
        width: 90px;
        unicode-bidi: bidi-override;
        direction: rtl;
        text-align: center;
        position: relative;
    }
    .rating > label {
        float: right;
        display: inline;
        padding: 0;
        margin: 0;
        position: relative;
        width: 1.1em;
        cursor: pointer;
        color: #000;
    }
    .rating > label:hover,
    .rating > label:hover ~ label,
    .rating > input.radio-btn:checked ~ label {
        color: transparent;
    }
    .rating > label:hover:before,
    .rating > label:hover ~ label:before,
    .rating > input.radio-btn:checked ~ label:before,
    .rating > input.radio-btn:checked ~ label:before {
        content: "\2605";
        position: absolute;
        left: 0;
        color: #FFD700;
    }
</style>
<div class="container-fluid">
    <h1>Product Details</h1>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($results as $item) : ?>
            <div class="col">
                <div class="card bg-light text-center">
                    <?php if (se($item, "image", "", false)) : ?>
                        <img src="<?php se($item, "image"); ?>" class="card-img-top" alt="...">
                    <?php endif; ?>
                </div>
            </div>
            <div class="col">
                <h3><?php se($item, "name"); ?></h3>
                <p>Description: <?php se($item, "description"); ?></p>
                <h5> Price: $<?php se($item, "unit_price"); ?> </h5>
                <div class="input-group">
                    <div class="input-group-text">Quantity</div>
                    <input class="form-control" type="number" id="quantity" name="quantity" min="1">
                    <button onclick="cart('<?php se($item, 'id'); ?>', document.getElementById('quantity').value)" class="btn btn-dark">Add to Cart</button>
                </div>
                <p></p>
                <div>
                    <h5> Rating: </h5>
                    <form method="POST">
                        <div class="rating">
                            <input id="star5" name="rating" type="radio" value="5" class="radio-btn hide" />
                            <label for="star5">☆</label>
                            <input id="star4" name="rating" type="radio" value="4" class="radio-btn hide" />
                            <label for="star4">☆</label>
                            <input id="star3" name="rating" type="radio" value="3" class="radio-btn hide" />
                            <label for="star3">☆</label>
                            <input id="star2" name="rating" type="radio" value="2" class="radio-btn hide" />
                            <label for="star2">☆</label>
                            <input id="star1" name="rating" type="radio" value="1" class="radio-btn hide" />
                            <label for="star1">☆</label>
                            <div class="clear"></div>
                        </div>
                        <div class="form-group">
                            <label for="comment">Leave a review</label>
                            <textarea class="form-control" id="comment" rows="3" name="comment"></textarea>
                        </div>
                        <input type="hidden" name="product" value="<?php echo $prod; ?>">
                        <button type="review" name="review" class="btn btn-dark mb-2">Submit Review</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="col">
            <?php if (empty($ratings)) : ?>
                <p>There are no ratings for this product.</p>
            <?php else : ?>
                <h5>Product Reviews: <?php echo number_format($avg_rating, 2) ?> stars (<?php echo $count ?> reviews)</h5>
            <?php endif; ?>
            <div class="accordion">
                <?php $c = 1; ?>
                <?php foreach ($ratings as $rating) : ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="panelsStayOpen-heading<?php echo $c; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapse<?php echo $c; ?>" aria-expanded="false" aria-controls="panelsStayOpen-collapse<?php echo $c; ?>">
                            <strong><?php se($rating, 'username') ?>&nbsp;</strong> rated <strong>&nbsp;<?php se($rating, 'rating') ?> stars</strong>
                        </button>
                        </h2>
                        <div id="panelsStayOpen-collapse<?php echo $c; ?>" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-heading<?php echo $c; ?>">
                            <div class="accordion-body">
                                <p><?php se($rating, 'comment') ?></p>
                                <p>on <strong><?php se($rating, 'created') ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <?php $c += 1; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
require(__DIR__ . "/../../partials/footer.php");
?>