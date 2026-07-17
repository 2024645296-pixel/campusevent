<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$userName = $_SESSION['name'] ?? 'User';

if (!function_exists('isActivePage')) {
    function isActivePage($pages, $currentPage)
    {
        if (!is_array($pages)) {
            $pages = [$pages];
        }

        return in_array(
            $currentPage,
            $pages,
            true
        ) ? 'active' : '';
    }
}

/* Mobile avatar initials */
$nameParts = preg_split(
    '/\s+/',
    trim($userName)
);

$mobileInitials = '';

if (!empty($nameParts[0])) {
    $mobileInitials .= substr(
        $nameParts[0],
        0,
        1
    );
}

if (count($nameParts) > 1) {
    $mobileInitials .= substr(
        end($nameParts),
        0,
        1
    );
}

$mobileInitials = strtoupper(
    $mobileInitials ?: 'U'
);
?>


<div class="mobile-header">

    <button type="button" id="mobileMenuButton" class="mobile-menu-button" aria-label="Open navigation menu"
        aria-controls="mainSidebar" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="mobile-logo">
        Campus<span>Event</span>
    </div>

    <div class="mobile-user-avatar" title="<?php echo htmlspecialchars($userName); ?>">
        <?php echo htmlspecialchars($mobileInitials); ?>
    </div>

</div>

<!-- Mobile overlay -->

<div class="sidebar-overlay" id="sidebarOverlay"></div>


<div class="sidebar" id="mainSidebar">

    <div class="sidebar-logo-row">

        <div class="logo">
            Campus<span>Event</span>
        </div>

        <button type="button" id="sidebarCloseButton" class="sidebar-close-button" aria-label="Close navigation menu">
            <i class="fa-solid fa-xmark"></i>
        </button>

    </div>

    <p class="sidebar-subtitle">
        Student Club Management
    </p>

    <div class="menu">

        <!-- DASHBOARD -->

        <a href="dashboard.php" class="<?php echo isActivePage(
            ['dashboard.php'],
            $currentPage
        ); ?>">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <?php if ($role === 'Admin') { ?>

            <a href="manage_proposals.php" class="<?php echo isActivePage(
                [
                    'manage_proposals.php',
                    'proposal_detail.php',
                    'proposal_report.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-file-circle-check"></i>
                <span>Manage Proposals</span>
            </a>

            <a href="events.php" class="<?php echo isActivePage(
                [
                    'events.php',
                    'event_detail.php',
                    'edit_event.php',
                    'delete_event.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Club Events</span>
            </a>

            <a href="registrations.php" class="<?php echo isActivePage(
                [
                    'registrations.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Registrations</span>
            </a>

            <a href="reports.php" class="<?php echo isActivePage(
                [
                    'reports.php',
                    'report_detail.php',
                    'report_document.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-chart-column"></i>
                <span>Reports</span>
            </a>

            <a href="users.php" class="<?php echo isActivePage(
                [
                    'users.php',
                    'edit_user.php',
                    'delete_user.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-users"></i>
                <span>Users</span>
            </a>

        <?php } ?>


        <?php if ($role === 'Club Leader') { ?>

            <a href="submit_proposal.php" class="<?php echo isActivePage(
                [
                    'submit_proposal.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-file-circle-plus"></i>
                <span>Submit Proposal</span>
            </a>

            <a href="my_proposals.php" class="<?php echo isActivePage(
                [
                    'my_proposals.php',
                    'proposal_detail.php',
                    'proposal_report.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-folder-open"></i>
                <span>My Proposals</span>
            </a>

            <a href="events.php" class="<?php echo isActivePage(
                [
                    'events.php',
                    'event_detail.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Club Events</span>
            </a>

            <a href="profile.php" class="<?php echo isActivePage(
                [
                    'profile.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-user"></i>
                <span>My Profile</span>
            </a>

        <?php } ?>


        <?php if ($role === 'Student') { ?>

            <a href="events.php" class="<?php echo isActivePage(
                [
                    'events.php',
                    'event_detail.php',
                    'register_event.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Club Events</span>
            </a>

            <a href="my_registrations.php" class="<?php echo isActivePage(
                [
                    'my_registrations.php',
                    'registration_detail.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-ticket"></i>
                <span>My Registrations</span>
            </a>

            <a href="student_certificates.php" class="<?php echo isActivePage(
                [
                    'student_certificates.php',
                    'certificate.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-award"></i>
                <span>My Certificates</span>
            </a>

            <a href="profile.php" class="<?php echo isActivePage(
                [
                    'profile.php'
                ],
                $currentPage
            ); ?>">
                <i class="fa-solid fa-user"></i>
                <span>My Profile</span>
            </a>

        <?php } ?>


        <?php if (
            in_array(
                $role,
                [
                    'Admin',
                    'Club Leader',
                    'Student'
                ],
                true
            )
        ) { ?>

            <button type="button" id="themeToggle" class="theme-toggle" aria-label="Switch to dark mode">
                <i class="fa-solid fa-moon" id="themeIcon"></i>

                <span id="themeText">
                    Dark Mode
                </span>
            </button>

        <?php } ?>


        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </div>

</div>


<script>
    (function () {
        const storageKey = "campusEventTheme";

        function applyTheme(theme) {
            const isDark = theme === "dark";

            document.body.classList.toggle(
                "dark-mode",
                isDark
            );

            document.documentElement.setAttribute(
                "data-theme",
                theme
            );

            const themeToggle =
                document.getElementById("themeToggle");

            const themeIcon =
                document.getElementById("themeIcon");

            const themeText =
                document.getElementById("themeText");

            if (
                !themeToggle ||
                !themeIcon ||
                !themeText
            ) {
                return;
            }

            themeIcon.className = isDark
                ? "fa-solid fa-sun"
                : "fa-solid fa-moon";

            themeText.textContent = isDark
                ? "Light Mode"
                : "Dark Mode";

            themeToggle.setAttribute(
                "aria-label",
                isDark
                    ? "Switch to light mode"
                    : "Switch to dark mode"
            );
        }

        const savedTheme =
            localStorage.getItem(storageKey) || "light";

        applyTheme(savedTheme);

        const themeToggle =
            document.getElementById("themeToggle");

        if (themeToggle) {
            themeToggle.addEventListener(
                "click",
                function () {
                    const isCurrentlyDark =
                        document.body.classList.contains(
                            "dark-mode"
                        );

                    const newTheme =
                        isCurrentlyDark
                            ? "light"
                            : "dark";

                    localStorage.setItem(
                        storageKey,
                        newTheme
                    );

                    applyTheme(newTheme);
                }
            );
        }
    })();
</script>


<script>
    (function () {
        const mobileBreakpoint = 768;

        const sidebar =
            document.getElementById("mainSidebar");

        const overlay =
            document.getElementById("sidebarOverlay");

        const openButton =
            document.getElementById("mobileMenuButton");

        const closeButton =
            document.getElementById("sidebarCloseButton");

        if (
            !sidebar ||
            !overlay ||
            !openButton
        ) {
            return;
        }

        function openSidebar() {
            sidebar.classList.add("mobile-open");
            overlay.classList.add("active");

            document.body.classList.add(
                "mobile-sidebar-active"
            );

            openButton.setAttribute(
                "aria-expanded",
                "true"
            );
        }

        function closeSidebar() {
            sidebar.classList.remove("mobile-open");
            overlay.classList.remove("active");

            document.body.classList.remove(
                "mobile-sidebar-active"
            );

            openButton.setAttribute(
                "aria-expanded",
                "false"
            );
        }

        openButton.addEventListener(
            "click",
            function () {
                const isOpen =
                    sidebar.classList.contains(
                        "mobile-open"
                    );

                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }
        );

        overlay.addEventListener(
            "click",
            closeSidebar
        );

        if (closeButton) {
            closeButton.addEventListener(
                "click",
                closeSidebar
            );
        }

        sidebar
            .querySelectorAll(".menu a")
            .forEach(function (link) {
                link.addEventListener(
                    "click",
                    function () {
                        if (
                            window.innerWidth <=
                            mobileBreakpoint
                        ) {
                            closeSidebar();
                        }
                    }
                );
            });

        document.addEventListener(
            "keydown",
            function (event) {
                if (event.key === "Escape") {
                    closeSidebar();
                }
            }
        );

        window.addEventListener(
            "resize",
            function () {
                if (
                    window.innerWidth >
                    mobileBreakpoint
                ) {
                    closeSidebar();
                }
            }
        );
    })();
</script>