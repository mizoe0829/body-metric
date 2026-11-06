<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'BodyMetric') }} // 体組成・トレーニング管理＆AI助言ダッシュボード</title>

    <!-- Meta Description & Author -->
    <meta name="description" content="毎日の計測値（体重・体脂肪率・筋トレ）を1行入力するだけでAIが自動構造化＆Gemini 3.5 Flashが目標達成へのアプローチと次の一手を処方するフィットネス管理ツール。">
    <meta name="keywords" content="BodyMetric, フィットネス, 体重管理, 体脂肪率, 筋トレ記録, Gemini, AI助言, mizoe">
    <meta name="author" content="mizoe">
    <meta name="theme-color" content="#10b981">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:title" content="BodyMetric // 体組成・トレーニング管理＆AI助言ダッシュボード">
    <meta property="og:description" content="毎日の計測値（体重・体脂肪率・筋トレ）を1行入力するだけでAIが自動構造化＆Gemini 3.5 Flashが目標達成へのアプローチと次の一手を処方するフィットネス管理ツール。">
    <meta property="og:site_name" content="BodyMetric">
    <meta property="og:locale" content="ja_JP">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BodyMetric // 体組成・トレーニング管理＆AI助言ダッシュボード">
    <meta name="twitter:description" content="毎日の計測値（体重・体脂肪率・筋トレ）を1行入力するだけでAIが自動構造化＆Gemini 3.5 Flashが目標達成へのアプローチと次の一手を処方するフィットネス管理ツール。">
    <meta name="twitter:creator" content="@mizoe0829">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full font-sans text-slate-800 bg-slate-50 selection:bg-emerald-500 selection:text-white">
    @inertia
</body>
</html>

