<?php

require_once __DIR__ . '/Database.php';

class CartRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? (new Database())->pdo;
    }

    public function addItem(int $userId, int $productId, int $quantity, array $data = [], array $files = []): void {
        $quantity = max(1, $quantity);
        $rentalTermsAccepted = !empty($data['rental_terms_accepted']) ? 1 : 0;

        $this->pdo->beginTransaction();
        try {
            $product = $this->getProductForUpdate($productId, true);
            if (!$product) {
                throw new Exception('Product not found.');
            }

            $categoryType = strtolower($product['category_type'] ?? 'product');

            if ($categoryType !== 'service' && $quantity > (int)$product['prod_stock']) {
                throw new Exception('Quantity cannot exceed available stock.');
            }

            if ($categoryType === 'rental') {
                $cartItemId = $this->insertBaseItem($userId, $productId, $quantity);
                $this->insertRentalDetails($cartItemId, $data, 'cart', $rentalTermsAccepted);
            } elseif ($categoryType === 'service') {
                $savedFiles = $this->saveServiceFilesFromUpload($files);
                if (empty($savedFiles)) {
                    throw new Exception('Please upload at least one file for this service.');
                }

                $serviceQty = max(1, count($savedFiles), $quantity);
                $cartItemId = $this->insertBaseItem($userId, $productId, $serviceQty);
                $this->insertServiceDetails($cartItemId, $data, 'cart');
                foreach ($savedFiles as $file) {
                    $this->insertServiceFile($cartItemId, $file, 'cart');
                }
            } else {
                $existing = $this->findSimpleProductCartItem($userId, $productId);
                if ($existing) {
                    $newQty = (int)$existing['quantity'] + $quantity;
                    if ($newQty > (int)$product['prod_stock']) {
                        throw new Exception('Quantity cannot exceed available stock.');
                    }
                    $stmt = $this->pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND user_id = ?");
                    $stmt->execute([$newQty, $existing['cart_item_id'], $userId]);
                } else {
                    $this->insertBaseItem($userId, $productId, $quantity);
                }
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getItemsByUser(int $userId): array {
        $stmt = $this->pdo->prepare(" 
            SELECT 
                ci.cart_item_id,
                ci.product_id,
                p.user_id,
                ci.quantity,
                p.prod_name,
                p.prod_price,
                p.prod_image,
                p.rate_type AS prod_rate_type,
                p.prod_stock,
                p.rental_terms,
                c.category_type,
                rd.date_from,
                rd.date_to,
                rd.borrower_name AS full_name,
                rd.student_no,
                rd.age,
                rd.gender,
                rd.payment_status,
                rd.payment_proof_path,
                rd.payment_verified_at,
                rd.payment_verified_by,
                rd.payment_rejection_reason,
                rd.reservation_status,
                rd.rental_terms_accepted,
                sd.full_name AS service_full_name,
                sd.student_no AS service_student_no,
                sd.print_type,
                sd.file_count,
                GROUP_CONCAT(DISTINCT sf.original_filename ORDER BY sf.service_file_id SEPARATOR '||') AS service_files_text
            FROM cart_items ci
            JOIN products p ON p.prod_id = ci.product_id
            LEFT JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN rental_details rd ON rd.ref_type = 'cart' AND rd.ref_id = ci.cart_item_id
            LEFT JOIN service_details sd ON sd.ref_type = 'cart' AND sd.ref_id = ci.cart_item_id
            LEFT JOIN service_files sf ON sf.ref_type = 'cart' AND sf.ref_id = ci.cart_item_id
            WHERE ci.user_id = ?
            GROUP BY ci.cart_item_id
            ORDER BY ci.cart_item_id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getSelectedItems(int $userId, array $cartItemIds): array {
        $cartItemIds = array_values(array_unique(array_filter(array_map('intval', $cartItemIds), fn($id) => $id > 0)));
        if (empty($cartItemIds)) return [];

        $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
        $params = array_merge([$userId], $cartItemIds);
        $stmt = $this->pdo->prepare(" 
            SELECT 
                ci.cart_item_id,
                ci.product_id,
                p.user_id,
                ci.quantity,
                p.prod_name,
                p.prod_price,
                p.prod_image,
                p.rate_type AS prod_rate_type,
                p.prod_stock,
                p.rental_terms,
                c.category_type,
                rd.date_from,
                rd.date_to,
                rd.borrower_name AS full_name,
                rd.student_no,
                rd.age,
                rd.gender,
                rd.payment_status,
                rd.payment_proof_path,
                rd.payment_verified_at,
                rd.payment_verified_by,
                rd.payment_rejection_reason,
                rd.reservation_status,
                rd.rental_terms_accepted,
                sd.full_name AS service_full_name,
                sd.student_no AS service_student_no,
                sd.print_type,
                sd.file_count,
                GROUP_CONCAT(DISTINCT sf.original_filename ORDER BY sf.service_file_id SEPARATOR '||') AS service_files_text
            FROM cart_items ci
            JOIN products p ON p.prod_id = ci.product_id
            LEFT JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN rental_details rd ON rd.ref_type = 'cart' AND rd.ref_id = ci.cart_item_id
            LEFT JOIN service_details sd ON sd.ref_type = 'cart' AND sd.ref_id = ci.cart_item_id
            LEFT JOIN service_files sf ON sf.ref_type = 'cart' AND sf.ref_id = ci.cart_item_id
            WHERE ci.user_id = ? AND ci.cart_item_id IN ($placeholders)
            GROUP BY ci.cart_item_id
            ORDER BY ci.cart_item_id DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function removeItem(int $userId, int $cartItemId): void {
        $this->deleteRelatedCartDetails([$cartItemId]);
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND cart_item_id = ?");
        $stmt->execute([$userId, $cartItemId]);
    }

    public function deleteItems(int $userId, array $cartItemIds): void {
        $cartItemIds = array_values(array_unique(array_filter(array_map('intval', $cartItemIds), fn($id) => $id > 0)));
        if (empty($cartItemIds)) return;
        $this->deleteRelatedCartDetails($cartItemIds);
        $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
        $params = array_merge([$userId], $cartItemIds);
        $stmt = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND cart_item_id IN ($placeholders)");
        $stmt->execute($params);
    }

    public static function rentalDays(?string $from, ?string $to): int {
        if (empty($from) || empty($to)) return 1;
        $start = strtotime($from);
        $end = strtotime($to);
        if ($start === false || $end === false || $end < $start) return 1;
        return max(1, (int)floor(($end - $start) / 86400) + 1);
    }

    public static function subtotal(array $item): float {
        $price = (float)($item['prod_price'] ?? $item['unit_price'] ?? 0);
        $qty = (int)($item['quantity'] ?? 1);
        $categoryType = strtolower($item['category_type'] ?? $item['item_type'] ?? 'product');
        if ($categoryType === 'rental') {
            return $price * $qty * self::rentalDays($item['date_from'] ?? null, $item['date_to'] ?? null);
        }
        return $price * $qty;
    }

    private function getProductForUpdate(int $productId, bool $forUpdate = true): ?array {
        $sql = "SELECT p.*, c.category_type FROM products p LEFT JOIN categories c ON c.category_id = p.category_id WHERE p.prod_id = ? AND p.status = 'active'";
        if ($forUpdate) $sql .= " FOR UPDATE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function findSimpleProductCartItem(int $userId, int $productId): ?array {
        $stmt = $this->pdo->prepare(" 
            SELECT ci.*
            FROM cart_items ci
                        LEFT JOIN rental_details rd ON rd.ref_type = 'cart' AND rd.ref_id = ci.cart_item_id
                        LEFT JOIN service_details sd ON sd.ref_type = 'cart' AND sd.ref_id = ci.cart_item_id
            WHERE ci.user_id = ? AND ci.product_id = ?
                            AND rd.id IS NULL AND sd.id IS NULL
            LIMIT 1
        ");
        $stmt->execute([$userId, $productId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function insertBaseItem(int $userId, int $productId, int $quantity): int {
        $stmt = $this->pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $productId, $quantity]);
        return (int)$this->pdo->lastInsertId();
    }

    private function insertRentalDetails(int $cartItemId, array $data, string $refType = 'cart', int $rentalTermsAccepted = 0): void {
        $stmt = $this->pdo->prepare(" 
            INSERT INTO rental_details
            (ref_type, ref_id, date_from, date_to, rental_days, borrower_name, student_no, age, gender, payment_status, payment_proof_path, payment_verified_at, payment_verified_by, payment_rejection_reason, reservation_status, rental_terms_accepted)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $refType,
            $cartItemId,
            $data['date_from'] ?? date('Y-m-d'),
            $data['date_to'] ?? date('Y-m-d'),
            self::rentalDays($data['date_from'] ?? null, $data['date_to'] ?? null),
            trim($data['full_name'] ?? ''),
            trim($data['student_no'] ?? ''),
            isset($data['age']) && $data['age'] !== '' ? (int)$data['age'] : null,
            trim($data['gender'] ?? ''),
            $refType === 'order' ? 'Pending Payment' : null,
            $refType === 'order' ? ($data['payment_proof_path'] ?? null) : null,
            null,
            null,
            null,
            $refType === 'order' ? 'Pending Payment' : null,
            $rentalTermsAccepted
        ]);
    }

    private function insertServiceDetails(int $cartItemId, array $data, string $refType = 'cart'): void {
        $stmt = $this->pdo->prepare(" 
            INSERT INTO service_details (ref_type, ref_id, full_name, student_no, print_type, file_count)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $refType,
            $cartItemId,
            trim($data['full_name'] ?? ''),
            trim($data['student_no'] ?? ''),
            $data['print_type'] ?? 'B&W',
            (int)($data['file_count'] ?? 1)
        ]);
    }

    private function insertServiceFile(int $cartItemId, array $file, string $refType = 'cart'): void {
        $stmt = $this->pdo->prepare(" 
            INSERT INTO service_files (ref_type, ref_id, original_filename, stored_filename, file_path)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$refType, $cartItemId, $file['original'], $file['stored'], $file['path']]);
    }

    private function deleteRelatedCartDetails(array $cartItemIds): void {
        if (empty($cartItemIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
        $this->pdo->prepare("DELETE FROM rental_details WHERE ref_type = 'cart' AND ref_id IN ($placeholders)")->execute($cartItemIds);
        $this->pdo->prepare("DELETE FROM service_details WHERE ref_type = 'cart' AND ref_id IN ($placeholders)")->execute($cartItemIds);
        $this->pdo->prepare("DELETE FROM service_files WHERE ref_type = 'cart' AND ref_id IN ($placeholders)")->execute($cartItemIds);
    }

    private function saveServiceFilesFromUpload(array $files): array {
        $saved = [];
        $uploadDir = __DIR__ . '/../public/uploads/service_files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Accept both service_file and service_files[] forms.
        $input = $files['service_files'] ?? $files['service_file'] ?? null;
        if (!$input) return $saved;

        $names = is_array($input['name']) ? $input['name'] : [$input['name']];
        $tmpNames = is_array($input['tmp_name']) ? $input['tmp_name'] : [$input['tmp_name']];
        $errors = is_array($input['error']) ? $input['error'] : [$input['error']];

        foreach ($names as $i => $original) {
            if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $tmp = $tmpNames[$i] ?? null;
            if (!$tmp || !is_uploaded_file($tmp)) continue;

            $safeOriginal = basename($original);
            $ext = strtolower(pathinfo($safeOriginal, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','ppt','pptx','xls','xlsx','txt'];
            if ($ext && !in_array($ext, $allowed, true)) continue;

            $stored = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');
            $dest = $uploadDir . $stored;
            if (move_uploaded_file($tmp, $dest)) {
                $saved[] = [
                    'original' => $safeOriginal,
                    'stored' => $stored,
                    'path' => 'uploads/service_files/' . $stored,
                ];
            }
        }
        return $saved;
    }
}
