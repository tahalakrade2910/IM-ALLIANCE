<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/bootstrap.php';

use App\Database;

$db = new Database($config['database']);
$pdo = $db->pdo();

$currentUser = $_SESSION['username'] ?? 'Utilisateur';
$baseUrl = '.';

$initialMessages = [];

try {
    $statement = $pdo->query('SELECT id, sender_id, sender_name, message, created_at FROM chat_messages ORDER BY created_at DESC LIMIT 100');
    $initialMessages = $statement->fetchAll();
    $initialMessages = array_reverse($initialMessages);
} catch (PDOException $exception) {
    $initialMessages = [];
}

$initialPayload = json_encode(
    array_map(
        static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'sender_id' => (int) ($row['sender_id'] ?? 0),
                'sender_name' => (string) ($row['sender_name'] ?? ''),
                'message' => (string) ($row['message'] ?? ''),
                'created_at' => isset($row['created_at']) ? date(DATE_ATOM, strtotime((string) $row['created_at'])) : date(DATE_ATOM),
            ];
        },
        $initialMessages
    ),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

if ($initialPayload === false) {
    $initialPayload = '[]';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussions - Application unifiée</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/imalliance-logo.svg" />
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<?php require __DIR__ . '/partials/top_nav.php'; ?>
<main class="chat-wrapper">
    <section class="chat-card">
        <header class="chat-header">
            <h1>Espace de discussion</h1>
            <p>Échangez rapidement avec les autres utilisateurs connectés à l'application.</p>
        </header>
        <div class="chat-body">
            <ul class="chat-messages" id="chat-messages" aria-live="polite"></ul>
        </div>
        <form class="chat-form" id="chat-form" autocomplete="off">
            <label class="chat-form__label" for="chat-message">Envoyer un message</label>
            <div class="chat-form__controls">
                <textarea id="chat-message" name="message" rows="2" maxlength="1000" placeholder="Votre message..." required></textarea>
                <button type="submit">Envoyer</button>
            </div>
            <p class="chat-form__help">Connecté en tant que <strong><?php echo htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p class="chat-form__error" id="chat-error" role="alert" hidden></p>
        </form>
    </section>
</main>
<script>
(function() {
    const messagesContainer = document.getElementById('chat-messages');
    const form = document.getElementById('chat-form');
    const messageInput = document.getElementById('chat-message');
    const errorMessage = document.getElementById('chat-error');

    /** @type {{id:number,sender_id:number,sender_name:string,message:string,created_at:string}[]} */
    const initialMessages = <?php echo $initialPayload; ?>;
    let lastMessageId = 0;
    let isSending = false;

    const renderMessage = (message) => {
        const listItem = document.createElement('li');
        listItem.className = 'chat-message';

        const header = document.createElement('div');
        header.className = 'chat-message__header';

        const sender = document.createElement('span');
        sender.className = 'chat-message__sender';
        sender.textContent = message.sender_name || 'Utilisateur';
        header.appendChild(sender);

        const timestamp = document.createElement('time');
        timestamp.className = 'chat-message__time';
        const date = message.created_at ? new Date(message.created_at) : new Date();
        timestamp.dateTime = date.toISOString();
        timestamp.textContent = date.toLocaleString('fr-FR');
        header.appendChild(timestamp);

        const content = document.createElement('p');
        content.className = 'chat-message__content';
        content.textContent = message.message;

        listItem.appendChild(header);
        listItem.appendChild(content);

        messagesContainer.appendChild(listItem);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };

    const renderMessages = (messages, replace = false) => {
        if (replace) {
            messagesContainer.innerHTML = '';
        }

        messages.forEach((message) => {
            renderMessage(message);
            lastMessageId = Math.max(lastMessageId, message.id);
        });
    };

    const fetchMessages = async () => {
        try {
            const response = await fetch(`chat_messages.php?after=${encodeURIComponent(lastMessageId)}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            if (payload && Array.isArray(payload.messages) && payload.messages.length > 0) {
                renderMessages(payload.messages, false);
            }
        } catch (error) {
            console.error('Unable to fetch messages', error);
        }
    };

    renderMessages(initialMessages, true);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSending) {
            return;
        }

        const message = messageInput.value.trim();
        if (!message) {
            errorMessage.textContent = 'Votre message est vide.';
            errorMessage.hidden = false;
            return;
        }

        if (message.length > 1000) {
            errorMessage.textContent = 'Votre message est trop long.';
            errorMessage.hidden = false;
            return;
        }

        errorMessage.hidden = true;
        isSending = true;

        try {
            const response = await fetch('chat_send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message })
            });

            if (!response.ok) {
                let errorText = 'Une erreur est survenue lors de l\'envoi du message.';
                try {
                    const payload = await response.json();
                    if (payload && payload.error) {
                        errorText = payload.error;
                    }
                } catch (parseError) {
                    // ignore parsing errors
                }
                errorMessage.textContent = errorText;
                errorMessage.hidden = false;
                return;
            }

            messageInput.value = '';
            await fetchMessages();
        } catch (error) {
            errorMessage.textContent = 'Impossible d\'envoyer le message pour le moment.';
            errorMessage.hidden = false;
        } finally {
            isSending = false;
        }
    });

    setInterval(fetchMessages, 5000);
})();
</script>
</body>
</html>
