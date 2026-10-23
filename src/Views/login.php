<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PWA Builder Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            border-radius: 14px;
            border: none;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.01);
        }
    </style>
</head>
<body class="p-3">
<div class="card login-card bg-white p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
            <i class="bi bi-phone-vibrate-fill fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1">PWA Builder</h4>
        <small class="text-muted">Masuk untuk mengelola Progressive Web App</small>
    </div>

    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger py-2 small d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0"></i>
        <div><?= htmlspecialchars($errorMessage) ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= '/' . trim($routePrefix, '/') ?>/login">
        <?php if (!empty($csrf['name']) && !empty($csrf['value'])): ?>
        <input type="hidden" name="<?= htmlspecialchars($csrf['name']) ?>" value="<?= htmlspecialchars($csrf['value']) ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person text-secondary"></i></span>
                <input type="text" name="username" class="form-control" placeholder="admin" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key text-secondary"></i></span>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-text small text-muted mt-1">
                Kredensial dapat diatur melalui <code>PWA_AUTH_PASSWORD</code> di file <code>.env</code>.
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Dashboard
        </button>
    </form>
</div>
</body>
</html>

