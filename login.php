
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bible Portal | Grow in Faith</title>

<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #101b2c;
    color: white;
    min-height: 100vh;
}

.hero {
    min-height: 100vh;
    background:
        linear-gradient(90deg, rgba(10,19,34,.94), rgba(10,19,34,.68), rgba(10,19,34,.35)),
        url("https://images.unsplash.com/photo-1504052434569-70ad5836ab65?auto=format&fit=crop&w=2000&q=85");
    background-size: cover;
    background-position: center;
    display: flex;
    flex-direction: column;
}

nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 8%;
}

.logo {
    font-family: 'Playfair Display', serif;
    font-size: 27px;
    font-weight: bold;
    color: #f3cf8b;
}

.nav-btn {
    border: 1px solid #f3cf8b;
    color: #f3cf8b;
    background: transparent;
    padding: 10px 22px;
    border-radius: 30px;
    cursor: pointer;
    font-family: inherit;
}

.nav-btn:hover {
    background: #f3cf8b;
    color: #172238;
}

.content {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 50px 8%;
}

.hero-text {
    max-width: 720px;
}

.eyebrow {
    color: #f3cf8b;
    letter-spacing: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 22px;
}

h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(42px, 6vw, 76px);
    line-height: 1.15;
    margin-bottom: 22px;
}

h1 span {
    color: #f3cf8b;
}

.description {
    color: #e0e4eb;
    font-size: 16px;
    line-height: 1.9;
    max-width: 590px;
    margin-bottom: 32px;
}

.buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.primary-btn, .outline-btn {
    padding: 15px 28px;
    border-radius: 30px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: .3s;
}

.primary-btn {
    background: #e6bd76;
    border: 1px solid #e6bd76;
    color: #172238;
}

.primary-btn:hover {
    background: #f5d69f;
    transform: translateY(-2px);
}

.outline-btn {
    background: transparent;
    border: 1px solid white;
    color: white;
}

.outline-btn:hover {
    background: white;
    color: #172238;
}

.features {
    display: flex;
    flex-wrap: wrap;
    gap: 28px;
    margin-top: 55px;
}

.feature {
    max-width: 180px;
    color: #e3e6eb;
    font-size: 13px;
    line-height: 1.7;
}

.feature strong {
    display: block;
    color: #f3cf8b;
    font-size: 14px;
    margin-bottom: 5px;
}

.quote {
    margin-top: 40px;
    color: #f3cf8b;
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-size: 17px;
}

footer {
    padding: 20px;
    text-align: center;
    color: #b8c0ce;
    font-size: 12px;
}

/* Authentication popup */

.modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 10;
    background: rgba(0,0,0,.78);
    justify-content: center;
    align-items: center;
    padding: 18px;
}

.modal.show {
    display: flex;
}

.auth-box {
    position: relative;
    background: #fff;
    color: #263247;
    width: 100%;
    max-width: 420px;
    padding: 35px;
    border-radius: 18px;
    text-align: center;
    max-height: 90vh;
    overflow-y: auto;
}

.auth-box h2 {
    font-family: 'Playfair Display', serif;
    font-size: 29px;
    margin-bottom: 10px;
}

.auth-box p {
    color: #667085;
    font-size: 13px;
    margin-bottom: 20px;
}

.close {
    position: absolute;
    right: 17px;
    top: 12px;
    border: none;
    background: none;
    font-size: 25px;
    cursor: pointer;
    color: #555;
}

.auth-box input {
    width: 100%;
    padding: 14px;
    margin: 8px 0;
    border: 1px solid #d6dbe3;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
}

.auth-submit {
    width: 100%;
    padding: 14px;
    margin-top: 12px;
    background: #263b63;
    color: white;
    border: none;
    border-radius: 8px;
    font-family: inherit;
    font-weight: 600;
    cursor: pointer;
}

.auth-submit:hover {
    background: #405d91;
}

.switch-link {
    display: block;
    margin-top: 18px;
    color: #344e7d;
    font-size: 13px;
    cursor: pointer;
    text-decoration: underline;
}

#message {
    margin-top: 15px;
    font-size: 13px;
    overflow-wrap: anywhere;
}

@media(max-width: 600px) {
    nav {
        padding: 20px 6%;
    }

    .content {
        padding: 55px 7%;
    }

    .description {
        font-size: 14px;
    }

    .features {
        gap: 20px;
        margin-top: 40px;
    }

    .feature {
        max-width: 145px;
    }

    .auth-box {
        padding: 30px 22px;
    }
}
</style>
</head>

<body>

<section class="hero">

<nav>
    <div class="logo">✝ Bible Portal</div>
    <button class="nav-btn" onclick="openAuth('login')">
        Sign In
    </button>
</nav>

<div class="content">
<div class="hero-text">

    <div class="eyebrow">YOUR FAITH, JOURNEY AND, DAILY WORD.</div>

    <h1>
        Don't Just Read the Word.
        <span>Live It.</span>
    </h1>

    <p class="description">
        Life has questions. Faith brings direction.
        Discover the wisdom of Scripture, find encouragement
        for every season, and build a deeper relationship
        with God — one day and one chapter at a time.
    </p>

    <div class="buttons">
        <button class="primary-btn" onclick="openAuth('signup')">
            Start Your Journey →
        </button>

        <button class="outline-btn" onclick="openAuth('login')">
            Welcome Back
        </button>
    </div>

    <div class="features">
        <div class="feature">
            <strong>📖 Explore Scripture</strong>
            Discover God's Word and its timeless wisdom.
        </div>

        <div class="feature">
            <strong>❤️ Save Your Favorites</strong>
            Keep meaningful verses close to your heart.
        </div>

        <div class="feature">
            <strong>🌅 Grow Every Day</strong>
            Make time for reflection and spiritual growth.
        </div>
    </div>

    <div class="quote">
        "Your word is a lamp to my feet and a light to my path."
        <br>— Psalm 119:105
    </div>

</div>
</div>

<footer>
    A place to discover, reflect, and grow in faith.
</footer>

</section>

<!-- Authentication modal -->

<div class="modal" id="authModal">
<div class="auth-box">

    <button class="close" onclick="closeAuth()">×</button>

    <h2 id="formTitle">Welcome Back</h2>
    <p id="formDescription">Sign in and continue your journey in faith.</p>

    <input type="email" id="email" placeholder="Email address" autocomplete="email">

    <input type="password" id="password" placeholder="Password" autocomplete="current-password">

    <input type="password" id="confirmPassword" placeholder="Confirm password" autocomplete="new-password" style="display:none;">

    <button class="auth-submit" id="submitBtn" onclick="submitAuth()">
        Sign In
    </button>

    <span class="switch-link" id="switchLink" onclick="switchAuth()">
        Don't have an account? Create one
    </span>

    <span class="switch-link" id="forgotLink" onclick="forgotPassword()">
        Forgot your password?
    </span>

    <div id="message" role="status" aria-live="polite"></div>

</div>
</div>

<script>
const SUPABASE_URL = "https://bqvevqwclrofwaijvxjf.supabase.co";
const SUPABASE_KEY = "sb_publishable_MlGy-3aUho7FK4SWhj1wfA_n9AstYaw";

const supabaseClient = window.supabase.createClient(
    SUPABASE_URL,
    SUPABASE_KEY
);

let authMode = "login";

const modal = document.getElementById("authModal");
const message = document.getElementById("message");

function openAuth(mode) {
    authMode = mode;
    modal.classList.add("show");
    updateAuthForm();
}

function closeAuth() {
    modal.classList.remove("show");
    message.textContent = "";
}

function switchAuth() {
    authMode = authMode === "login" ? "signup" : "login";
    updateAuthForm();
}

function updateAuthForm() {
    const signup = authMode === "signup";

    document.getElementById("formTitle").textContent =
        signup ? "Begin Your Journey" : "Welcome Back";

    document.getElementById("formDescription").textContent =
        signup
        ? "Create your account and grow in God's Word."
        : "Sign in and continue your journey in faith.";

    document.getElementById("submitBtn").textContent =
        signup ? "Create My Account" : "Sign In";

    document.getElementById("confirmPassword").style.display =
        signup ? "block" : "none";

    document.getElementById("switchLink").textContent =
        signup
        ? "Already have an account? Sign in"
        : "Don't have an account? Create one";

    document.getElementById("forgotLink").style.display =
        signup ? "none" : "block";

    message.textContent = "";
}

function showMessage(text, success = false) {
    message.textContent = text;
    message.style.color = success ? "green" : "red";
}

async function submitAuth() {
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (!email || !password) {
        return showMessage("Please enter your email and password.");
    }

    if (password.length < 6) {
        return showMessage("Your password must have at least 6 characters.");
    }

    const button = document.getElementById("submitBtn");
    button.disabled = true;
    button.textContent = "Please wait...";

    try {
        if (authMode === "signup") {
            if (password !== confirmPassword) {
                return showMessage("Your passwords do not match.");
            }

            const { data, error } = await supabaseClient.auth.signUp({
                email: email,
                password: password,
                options: {
                    emailRedirectTo: window.location.origin + "/bible-portal/"
                }
            });

            if (error) {
                showMessage(error.message);
            } else if (data.session) {
                showMessage("Account created! Welcome to Bible Portal.", true);
                setTimeout(() => {
                    window.location.href = "/bible-portal/";
                }, 1200);
            } else {
                showMessage(
                    "Account created! Please check your email and confirm your account before signing in.",
                    true
                );
            }

        } else {
            const { data, error } = await supabaseClient.auth.signInWithPassword({
                email: email,
                password: password
            });

            if (error) {
                showMessage(error.message);
            } else if (data.session) {
                showMessage("Welcome back! Redirecting...", true);
                setTimeout(() => {
                    window.location.href = "/bible-portal/";
                }, 1000);
            }
        }

    } catch (error) {
        showMessage("Something went wrong. Please try again.");
    } finally {
        button.disabled = false;
        button.textContent = authMode === "signup"
            ? "Create My Account"
            : "Sign In";
    }
}

async function forgotPassword() {
    const email = document.getElementById("email").value.trim();

    if (!email) {
        return showMessage("Please enter your email address first.");
    }

    const { error } = await supabaseClient.auth.resetPasswordForEmail(email, {
        redirectTo: window.location.origin + "/bible-portal/login.php"
    });

    if (error) {
        showMessage(error.message);
    } else {
        showMessage("Password reset instructions have been sent to your email.", true);
    }
}

modal.addEventListener("click", function(event) {
    if (event.target === modal) {
        closeAuth();
    }
});
</script>

</body>
</html>
