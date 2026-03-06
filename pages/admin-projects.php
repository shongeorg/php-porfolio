<?php
/**
 * Admin projects management - CRUD operations
 */
requireAdmin();

$message = '';
$messageType = '';
$editProject = null;

// Handle actions
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Delete project
if ($action === 'delete' && $id > 0) {
    if (projectDelete($id)) {
        $message = 'Проєкт видалено';
        $messageType = 'success';
    } else {
        $message = 'Помилка при видаленні';
        $messageType = 'error';
    }
}

// Toggle active status
if ($action === 'toggle' && $id > 0) {
    if (projectToggleActive($id)) {
        $message = 'Статус змінено';
        $messageType = 'success';
    } else {
        $message = 'Помилка при зміні статусу';
        $messageType = 'error';
    }
}

// Handle form submission (create/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'github_url' => trim($_POST['github_url'] ?? ''),
        'site_url' => trim($_POST['site_url'] ?? ''),
        'tags' => trim($_POST['tags'] ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    $errors = projectValidate($data);

    if (empty($errors)) {
        $editId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($editId > 0) {
            // Update existing
            if (projectUpdate($editId, $data)) {
                $message = 'Проєкт оновлено';
                $messageType = 'success';
            } else {
                $message = 'Помилка при оновленні';
                $messageType = 'error';
            }
        } else {
            // Create new
            if (projectCreate($data)) {
                $message = 'Проєкт створено';
                $messageType = 'success';
            } else {
                $message = 'Помилка при створенні';
                $messageType = 'error';
            }
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}

// Get project for editing
if ($action === 'edit' && $id > 0) {
    $editProject = projectGetById($id);
}

// Get all projects
$projects = projectGetAll();
?>

<section class="admin-projects">
    <div class="admin-projects__container">
        <div class="admin-projects__header">
            <h1 class="admin-projects__title">Управління проєктами</h1>
            <a href="/?page=admin" class="admin-btn admin-btn--secondary">
                ← Назад у панель
            </a>
        </div>

        <?php if ($message): ?>
            <div class="message message--<?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($editProject): ?>
            <!-- Edit Form -->
            <div class="form-card">
                <h2 class="form-card__title">Редагувати проєкт</h2>
                <form class="project-form" method="POST" action="/?page=admin-projects">
                    <input type="hidden" name="id" value="<?= $editProject['id'] ?>">

                    <div class="form-group">
                        <label class="form-group__label" for="title">Назва *</label>
                        <input
                            class="form-group__input"
                            type="text"
                            id="title"
                            name="title"
                            value="<?= htmlspecialchars($editProject['title']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-group__label" for="description">Опис</label>
                        <textarea
                            class="form-group__input form-group__input--textarea"
                            id="description"
                            name="description"
                            rows="4"
                        ><?= htmlspecialchars($editProject['description']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-group__label" for="github_url">GitHub URL</label>
                            <input
                                class="form-group__input"
                                type="url"
                                id="github_url"
                                name="github_url"
                                value="<?= htmlspecialchars($editProject['github_url']) ?>"
                                placeholder="https://github.com/..."
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-group__label" for="site_url">Сайт URL</label>
                            <input
                                class="form-group__input"
                                type="url"
                                id="site_url"
                                name="site_url"
                                value="<?= htmlspecialchars($editProject['site_url']) ?>"
                                placeholder="https://..."
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-group__label" for="tags">Теги (через кому)</label>
                            <input
                                class="form-group__input"
                                type="text"
                                id="tags"
                                name="tags"
                                value="<?= htmlspecialchars($editProject['tags']) ?>"
                                placeholder="PHP, CSS, JavaScript"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-group__label" for="sort_order">Порядок</label>
                            <input
                                class="form-group__input"
                                type="number"
                                id="sort_order"
                                name="sort_order"
                                value="<?= $editProject['sort_order'] ?>"
                                min="0"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="is_active"
                                <?= $editProject['is_active'] ? 'checked' : '' ?>
                            >
                            <span class="checkbox-label__text">Активний</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="admin-btn admin-btn--primary">
                            Зберегти
                        </button>
                        <a href="/?page=admin-projects" class="admin-btn admin-btn--secondary">
                            Скасувати
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Create Form -->
            <div class="form-card">
                <h2 class="form-card__title">Новий проєкт</h2>
                <form class="project-form" method="POST" action="/?page=admin-projects">
                    <div class="form-group">
                        <label class="form-group__label" for="title">Назва *</label>
                        <input
                            class="form-group__input"
                            type="text"
                            id="title"
                            name="title"
                            required
                            placeholder="Мій проєкт"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-group__label" for="description">Опис</label>
                        <textarea
                            class="form-group__input form-group__input--textarea"
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Опис проєкту..."
                        ></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-group__label" for="github_url">GitHub URL</label>
                            <input
                                class="form-group__input"
                                type="url"
                                id="github_url"
                                name="github_url"
                                placeholder="https://github.com/..."
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-group__label" for="site_url">Сайт URL</label>
                            <input
                                class="form-group__input"
                                type="url"
                                id="site_url"
                                name="site_url"
                                placeholder="https://..."
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-group__label" for="tags">Теги (через кому)</label>
                            <input
                                class="form-group__input"
                                type="text"
                                id="tags"
                                name="tags"
                                placeholder="PHP, CSS, JavaScript"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-group__label" for="sort_order">Порядок</label>
                            <input
                                class="form-group__input"
                                type="number"
                                id="sort_order"
                                name="sort_order"
                                value="0"
                                min="0"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="is_active"
                                checked
                            >
                            <span class="checkbox-label__text">Активний</span>
                        </label>
                    </div>

                    <button type="submit" class="admin-btn admin-btn--primary">
                        Створити проєкт
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Projects List -->
        <div class="projects-table">
            <h2 class="projects-table__title">Усі проєкти</h2>

            <?php if (empty($projects)): ?>
                <p class="projects-table__empty">Проєктів поки що немає</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>Теги</th>
                            <th>Порядок</th>
                            <th>Статус</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?= $project['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($project['title']) ?></strong>
                                    <?php if ($project['site_url']): ?>
                                        <br><small>
                                            <a href="<?= htmlspecialchars($project['site_url']) ?>"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="data-table__link">
                                                🔗 <?= parse_url($project['site_url'], PHP_URL_HOST) ?>
                                            </a>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($project['tags'] ?? '-') ?></td>
                                <td><?= $project['sort_order'] ?></td>
                                <td>
                                    <?php if ($project['is_active']): ?>
                                        <span class="status-badge status-badge--active">✓ Активний</span>
                                    <?php else: ?>
                                        <span class="status-badge status-badge--inactive">✗ Прихований</span>
                                    <?php endif; ?>
                                </td>
                                <td class="data-table__actions">
                                    <a href="/?page=admin-projects&action=edit&id=<?= $project['id'] ?>"
                                       class="action-btn action-btn--edit"
                                       title="Редагувати">
                                        ✏️
                                    </a>
                                    <a href="/?page=admin-projects&action=toggle&id=<?= $project['id'] ?>"
                                       class="action-btn action-btn--toggle"
                                       title="Змінити статус">
                                        <?= $project['is_active'] ? '🙈' : '👁️' ?>
                                    </a>
                                    <a href="/?page=admin-projects&action=delete&id=<?= $project['id'] ?>"
                                       class="action-btn action-btn--delete"
                                       title="Видалити"
                                       onclick="return confirm('Видалити проєкт \'<?= htmlspecialchars($project['title']) ?>\'?')">
                                        🗑️
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>
