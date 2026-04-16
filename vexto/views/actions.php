<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/core/helpers.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'toggle_favorite') {
        $property_id = (int)$_POST['property_id'];
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
        $stmt->execute([$user_id, $property_id]);
        $fav = $stmt->fetch();

        if ($fav) {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
            $stmt->execute([$fav['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, property_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $property_id]);
        }
        header("Location: property_details.php?id=$property_id");

    } elseif ($action === 'schedule_appointment') {
        $property_id = (int)$_POST['property_id'];
        $seller_id = (int)$_POST['seller_id'];
        $fecha = $_POST['fecha'];
        
        if (empty($fecha)) {
            header("Location: property_details.php?id=$property_id&error=fecha_vacia");
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO appointments (user_id, seller_id, property_id, fecha_cita) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $seller_id, $property_id, $fecha]);
        header("Location: property_details.php?id=$property_id&appointment=success");
        exit();

    } elseif ($action === 'report') {
        $property_id = (int)$_POST['property_id'];
        $motivo = $_POST['motivo'];
        $stmt = $pdo->prepare("INSERT INTO reports (user_id, property_id, motivo) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $property_id, $motivo]);
        header("Location: property_details.php?id=$property_id&report=success");
        exit();

    } elseif ($action === 'add_review') {
        $seller_id = (int)$_POST['seller_id'];
        $stars = (int)$_POST['stars'];
        $comment = sanitize($_POST['comment']);
        $redirectTo = $_POST['redirect_to'] ?? "profile.php?id=$seller_id";

        if (!in_array($redirectTo, ["profile.php?id=$seller_id", "property_details.php?id=" . ($_POST['property_id'] ?? $seller_id)])) {
            $redirectTo = "profile.php?id=$seller_id";
        }

        if ($stars < 1 || $stars > 5) {
            header("Location: $redirectTo&error=invalid_rating");
            exit();
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE reviewer_id = ? AND seller_id = ?");
            $stmt->execute([$user_id, $seller_id]);
            $existingReview = $stmt->fetch();

            if ($existingReview) {
                $stmt = $pdo->prepare("UPDATE reviews SET stars = ?, comment = ? WHERE id = ?");
                $stmt->execute([$stars, $comment, $existingReview['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO reviews (reviewer_id, seller_id, stars, comment) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $seller_id, $stars, $comment]);
            }

            // Recalcular rating del vendedor
            $stmt = $pdo->prepare("SELECT AVG(stars) as avg_rating, COUNT(*) as total FROM reviews WHERE seller_id = ?");
            $stmt->execute([$seller_id]);
            $stats = $stmt->fetch();

            $stmt = $pdo->prepare("UPDATE users SET rating = ?, total_reviews = ? WHERE id = ?");
            $stmt->execute([$stats['avg_rating'], $stats['total'], $seller_id]);

            $pdo->commit();
            header("Location: $redirectTo&review=success");
        } catch (Exception $e) {
            $pdo->rollBack();
            logEvent('Review error: ' . $e->getMessage(), 'error');
            header("Location: $redirectTo&error=db_error");
        }
        exit();
    }
}
?>
