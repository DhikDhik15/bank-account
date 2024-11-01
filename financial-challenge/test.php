<?php
session_start();

class BankAccount {
    private string $username;
    private float $balance;
    private array $transactions = [];

    public function __construct(string $username, float $initialBalance = 0) {
        $this->username = $username;
        $this->balance = $initialBalance;
        $this->logTransaction('Initial Balance', 0, 0, $this->balance, 'Account created');
    }

    // Deposit function
    public function deposit(float $amount): string {
        if ($amount > 0) {
            $this->balance += $amount;
            $this->logTransaction('Deposit', $amount, 0, $this->balance);
            return "Deposit of $amount successful! New Balance: {$this->balance}.";
        }
        return "Deposit amount must be positive.";
    }

    // Withdraw function
    public function withdraw(float $amount): string {
        if ($amount > $this->balance) {
            return "Your balance is insufficient.";
        } elseif ($amount <= 0) {
            return "Withdrawal amount must be positive.";
        }
        $this->balance -= $amount;
        $this->logTransaction('Withdraw', 0, $amount, $this->balance);
        return "Withdrawal of $amount successful! New Balance: {$this->balance}.";
    }

    // Transfer function
    public function transfer(float $amount, BankAccount $recipient): string {
        if ($amount > $this->balance) {
            return "Your balance is insufficient for transfer.";
        } elseif ($amount <= 0) {
            return "Transfer amount must be positive.";
        }
        $this->withdraw($amount);
        $recipient->receiveTransfer($amount, $this->username);
        return "Transfer of $amount to {$recipient->getUsername()} successful! New Balance: {$this->balance}.";
    }

    // Record receiving transfer
    private function receiveTransfer(float $amount, string $fromUsername): void {
        $this->balance += $amount;
        $this->logTransaction('Transfer', $amount, 0, $this->balance, "Transfer from $fromUsername");
    }

    // Log each transaction
    private function logTransaction(string $type, float $debit, float $credit, float $balance, string $description = ''): void {
        $this->transactions[] = [
            'date' => date('Y-m-d H:i'),
            'type' => $type,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $balance,
            'description' => $description
        ];
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getBalance(): float {
        return $this->balance;
    }

    public function getTransactions(): array {
        return $this->transactions;
    }
}

// Session handling for user login
function login(string $username): string {
    $_SESSION['user'] = $username;
    if (!isset($_SESSION['accounts'][$username])) {
        $_SESSION['accounts'][$username] = new BankAccount($username);
    }
    return "Welcome, $username!";
}

function logout(): string {
    if (isset($_SESSION['user'])) {
        unset($_SESSION['user']);
        return "Logout successful.";
    }
    return "No user is logged in.";
}

function getCurrentAccount(): ?BankAccount {
    return $_SESSION['accounts'][$_SESSION['user']] ?? null;
}

// Handle login/logout
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $message = login($_POST['username']);
    } elseif (isset($_POST['logout'])) {
        $message = logout();
    } elseif (isset($_POST['action'])) {
        $account = getCurrentAccount();
        if ($account) {
            switch ($_POST['action']) {
                case 'deposit':
                    $message = $account->deposit((float)$_POST['amount']);
                    break;
                case 'withdraw':
                    $message = $account->withdraw((float)$_POST['amount']);
                    break;
                case 'transfer':
                    if (isset($_SESSION['accounts'][$_POST['recipient']])) {
                        $recipient = $_SESSION['accounts'][$_POST['recipient']];
                        $message = $account->transfer((float)$_POST['amount'], $recipient);
                    } else {
                        $message = "Recipient does not exist.";
                    }
                    break;
            }
        } else {
            $message = "No user is logged in.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Account</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: auto;">
    <h2>Simple Bank Account</h2>

    <div style="margin-bottom: 20px;">
        <?php if (!isset($_SESSION['user'])): ?>
            <form method="post" style="display: inline;">
                <input type="text" name="username" placeholder="Username" required>
                <button type="submit" name="login">Login</button>
            </form>
        <?php else: ?>
            <form method="post" style="display: inline;">
                <button type="submit" name="logout">Logout</button>
            </form>
            <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></p>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 20px;">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>

    <?php if (isset($_SESSION['user']) && $account = getCurrentAccount()): ?>
        <h3>Account Balance: $<?php echo number_format($account->getBalance(), 2); ?></h3>

        <h4>Make a Transaction</h4>
        <form method="post" style="margin-bottom: 10px;">
            <input type="number" name="amount" placeholder="Amount" step="0.01" required>
            <button type="submit" name="action" value="deposit">Deposit</button>
            <button type="submit" name="action" value="withdraw">Withdraw</button>
        </form>
        <form method="post" style="margin-bottom: 20px;">
            <input type="number" name="amount" placeholder="Amount" step="0.01" required>
            <input type="text" name="recipient" placeholder="Recipient Username" required>
            <button type="submit" name="action" value="transfer">Transfer</button>
        </form>

        <h4>Transaction History</h4>
        <table border="1" cellpadding="5" style="width: 100%; text-align: left;">
            <tr>
                <th>Time</th>
                <th>Type</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Balance</th>
                <th>Description</th>
            </tr>
            <?php foreach ($account->getTransactions() as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                    <td><?php echo $transaction['debit'] > 0 ? '$' . number_format($transaction['debit'], 2) : ''; ?></td>
                    <td><?php echo $transaction['credit'] > 0 ? '$' . number_format($transaction['credit'], 2) : ''; ?></td>
                    <td><?php echo '$' . number_format($transaction['balance'], 2); ?></td>
                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
