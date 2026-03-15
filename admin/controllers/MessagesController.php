<?php
namespace Admin;

use Srv\DB;
use Srv\Socket;

class MessagesController
{
    private int $perPage = 25;

    public function index(): array
    {
        $page = max(1, intval($_GET['p'] ?? 1));
        $offset = ($page - 1) * $this->perPage;

        $total = (int) db_value("SELECT COUNT(*) FROM messages");
        $messages = db_query("SELECT * FROM messages ORDER BY id DESC LIMIT {$this->perPage} OFFSET {$offset}");
        $totalPages = max(1, ceil($total / $this->perPage));

        foreach ($messages as &$msg) {
            $from = db_query("SELECT name FROM `character` WHERE id = " . intval($msg['character_from_id']) . " LIMIT 1");
            $msg['from_name'] = $from ? $from[0]['name'] : 'System (#' . $msg['character_from_id'] . ')';
        }

        return [
            'viewFile'   => ADMIN_DIR . '/views/messages/list.php',
            'messages'   => $messages,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => $totalPages,
        ];
    }

    public function compose(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
            if (!csrf_verify()) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'CSRF token invalid.'];
            } else {
                $subject = trim($_POST['subject'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $fromId = intval($_POST['from_id'] ?? 0);
                $broadcast = isset($_POST['broadcast']) && $_POST['broadcast'] === '1';

                if (empty($subject) || empty($message)) {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Subject and message are required.'];
                } else {
                    $now = time();

                    if ($broadcast) {
                        $chars = db_query("SELECT id FROM `character`");
                        $count = 0;
                        foreach ($chars as $char) {
                            $stmt = DB::$connection->prepare(
                                "INSERT INTO messages (character_from_id, character_to_ids, subject, message, flag, flag_value, ts_creation, readed) VALUES (?, ?, ?, ?, '', '', ?, 0)"
                            );
                            $stmt->execute([$fromId, ';' . $char['id'] . ';', $subject, $message, $now]);
                            $count++;
                        }
                        $_SESSION['flash'] = ['type' => 'success', 'message' => "Broadcast sent to {$count} characters."];
                        Socket::syncGameAll();
                    } else {
                        $toId = intval($_POST['to_id'] ?? 0);
                        if (!$toId) {
                            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Recipient character ID is required.'];
                        } else {
                            $stmt = DB::$connection->prepare(
                                "INSERT INTO messages (character_from_id, character_to_ids, subject, message, flag, flag_value, ts_creation, readed) VALUES (?, ?, ?, ?, '', '', ?, 0)"
                            );
                            $stmt->execute([$fromId, ';' . $toId . ';', $subject, $message, $now]);
                            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Message sent.'];
                            // Find user_id from character_id to push notification
                            $userId = db_value("SELECT user_id FROM `character` WHERE id = " . $toId . " LIMIT 1");
                            if ($userId) Socket::syncGame($userId);
                        }
                    }
                    header('Location: index.php?page=messages');
                    exit;
                }
            }
        }

        return [
            'viewFile' => ADMIN_DIR . '/views/messages/compose.php',
        ];
    }

    public function delete(): array
    {
        $id = intval($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && csrf_verify()) {
            $stmt = DB::$connection->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => "Message #{$id} deleted."];
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=messages'));
        exit;
    }
}
