<?php

body {
    background-color: #f5f7fb;
    color: #20283b;
    min-height: 100vh;
}

.hero {
    background: linear-gradient(135deg, #ffffff, #e9edff);
    border: 1px solid #e3e8f4;
    box-shadow: 0 8px 30px rgba(31, 45, 90, 0.06);
}

.hero h1 {
    max-width: 750px;
    color: #202b55;
}

.hero p {
    max-width: 650px;
    color: #58627a;
}

.stat-card {
    border: 1px solid #e5e9f2;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(31, 45, 90, 0.04);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(31, 45, 90, 0.09);
}

@media (max-width: 576px) {
    .hero h1 {
        font-size: 2rem;
    }

    main.container {
        padding-top: 1.5rem !important;
    }
}
