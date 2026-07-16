# 🎫 SCF Reservation System

<div align="center">

![PHP](https://img.shields.io/badge/PHP-84.7%25-777BB4?style=flat-square&logo=php)
![Twig](https://img.shields.io/badge/Twig-11.5%25-90c53f?style=flat-square)
![JavaScript](https://img.shields.io/badge/JavaScript-3.4%25-F7DF1E?style=flat-square&logo=javascript)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

[English](#english) | [日本語](#japanese)

</div>

---

## 📊 Project Statistics

<div align="center">

![Commits](https://img.shields.io/github/commit-activity/m/tomStory9/SCF_Reservation?style=flat-square&label=Commits&color=blue)
![Contributors](https://img.shields.io/github/contributors/tomStory9/SCF_Reservation?style=flat-square&label=Contributors&color=purple)
![Pull Requests](https://img.shields.io/github/issues-pr/tomStory9/SCF_Reservation?style=flat-square&label=Pull%20Requests&color=brightgreen)
![Issues](https://img.shields.io/github/issues/tomStory9/SCF_Reservation?style=flat-square&label=Issues&color=red)
![Repo Size](https://img.shields.io/github/repo-size/tomStory9/SCF_Reservation?style=flat-square&label=Repo%20Size&color=orange)
![Last Commit](https://img.shields.io/github/last-commit/tomStory9/SCF_Reservation?style=flat-square&label=Last%20Commit&color=lightgrey)

</div>

---

## 👥 Contributors

<div align="center">

![Contributors Graph](https://img.shields.io/github/contributors/tomStory9/SCF_Reservation?style=flat-square)

| Avatar | Contributor | Contributions |
|--------|-------------|----------------|
| [![tomStory9](https://avatars.githubusercontent.com/u/97254191?v=4&s=50)](https://github.com/tomStory9) | [**tomStory9**](https://github.com/tomStory9) | 👨‍💻 Lead Developer |
| [![Diodoru](https://avatars.githubusercontent.com/u/93346994?v=4&s=50)](https://github.com/Diodoru) | [**Diodoru**](https://github.com/Diodoru) | 👨‍💻 Developer |

### 🙏 Special Thanks

We appreciate all contributions and feedback from our community!

</div>

---

## English

### 📋 Overview

**SCF Reservation** is a modern, robust reservation management system built with PHP and Twig templates. It provides an intuitive interface for managing bookings, scheduling, and customer reservations with a clean and user-friendly design.

### ✨ Features

- 🗓️ **Intuitive Booking System** - Easy-to-use reservation interface
- 📊 **Advanced Scheduling** - Manage multiple time slots and availability
- 👥 **Customer Management** - Track and organize customer information
- 🔐 **Secure Authentication** - Protected access with role-based permissions including SSO (Google, LINE)
- 📱 **Responsive Design** - Mobile-friendly interface for all devices
- 📈 **Real-time Updates** - Live reservation status and notifications
- 🎨 **Modern UI/UX** - Clean and professional design
- 🌍 **Multi-language Support** - English, French, Japanese translations

### 🛠️ Tech Stack

- **Backend**: PHP 8.x
- **Template Engine**: Twig 2.x/3.x
- **Frontend**: JavaScript (Vanilla JS)
- **Database**: MySQL/PostgreSQL compatible
- **Architecture**: MVC Pattern
- **Authentication**: Symfony Security, OAuth2 (SSO)
- **Code Quality**: GrumPHP, PHP-CS-Fixer

### 📦 Installation

```bash
# Clone the repository
git clone https://github.com/tomStory9/SCF_Reservation.git
cd SCF_Reservation

# Install dependencies
composer install

# Configure environment
cp .env.example .env
nano .env  # Edit configuration as needed

# Setup database
php artisan migrate
php artisan seed

# Start development server
php -S localhost:8000
```

### 🚀 Quick Start

1. **Create a Reservation**
   - Navigate to the booking page
   - Select date, time, and service
   - Enter customer details
   - Confirm reservation

2. **Manage Reservations**
   - View all upcoming bookings
   - Edit or cancel existing reservations
   - Send reminders to customers

3. **Generate Reports**
   - Track booking statistics
   - View occupancy rates
   - Export reservation data

### 📁 Project Structure

```
SCF_Reservation/
├── src/
│   ├── Controllers/      # Business logic
│   ├── Models/          # Data models
│   ├── Views/           # Twig templates
│   └── Services/        # Service layer
├── public/
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Asset files
├── config/             # Configuration files
├── database/           # Migrations and seeds
└── tests/              # Unit & integration tests
```

### 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

### 🆘 Support

For support, please:
- Open an Issue on GitHub
- Check existing documentation
- Contact the development team

### 📞 Contact

**Author**: tomStory9  
**GitHub**: [@tomStory9](https://github.com/tomStory9)  
**Email**: [Your contact information]

---

## 日本語

### 📋 概要

**SCF Reservation** は、PHPとTwigテンプレートで構築された、モダンで堅牢な予約管理システムです。予約、スケジューリング、顧客予約管理を、クリーンで使いやすいデザインで提供します。

### ✨ 機能

- 🗓️ **直感的な予約システム** - 使いやすい予約インターフェース
- 📊 **高度なスケジューリング** - 複数のタイムスロットと空き状況を管理
- 👥 **顧客管理** - 顧客情報を追跡・整理
- 🔐 **セキュアな認証** - SSO（Google、LINE）を含むロールベースのアクセス権限で保護
- 📱 **レスポンシブデザイン** - すべてのデバイスに対応
- 📈 **リアルタイム更新** - ライブ予約ステータスと通知
- 🎨 **モダンなUI/UX** - クリーンで専門的なデザイン
- 🌍 **多言語対応** - 英語、フランス語、日本語の翻訳

### 🛠️ 技術スタック

- **バックエンド**: PHP 8.x
- **テンプレートエンジン**: Twig 2.x/3.x
- **フロントエンド**: JavaScript（バ���ラJS）
- **データベース**: MySQL/PostgreSQL対応
- **アーキテクチャ**: MVCパターン
- **認証**: Symfony Security、OAuth2（SSO）
- **コード品質**: GrumPHP、PHP-CS-Fixer

### 📦 インストール

```bash
# リポジトリをクローン
git clone https://github.com/tomStory9/SCF_Reservation.git
cd SCF_Reservation

# 依存関係をインストール
composer install

# 環境設定
cp .env.example .env
nano .env  # 必要に応じて設定を編集

# データベースセットアップ
php artisan migrate
php artisan seed

# 開発サーバーを起動
php -S localhost:8000
```

### 🚀 クイックスタート

1. **予約を作成**
   - 予約ページに移動
   - 日付、時間、サービスを選択
   - 顧客情報を入力
   - 予約を確認

2. **予約を管理**
   - すべての今後の予約を表示
   - 既存の予約を編集またはキャンセル
   - 顧客にリマインダーを送信

3. **レポートを生成**
   - 予約統計を追跡
   - 稼働率を表示
   - 予約データをエクスポート

### 📁 プロジェクト構成

```
SCF_Reservation/
├── src/
│   ├── Controllers/      # ビジネスロジック
│   ├── Models/          # データモデル
│   ├── Views/           # Twigテンプレート
│   └── Services/        # サービスレイヤー
├── public/
│   ├── css/            # スタイルシート
│   ├── js/             # JavaScriptファイル
│   └── images/         # アセットファイル
├── config/             # 設定ファイル
├── database/           # マイグレーションとシード
└── tests/              # ユニット・統合テスト
```

### 🤝 貢献

貢献を歓迎します！以下の手順に従ってください：

1. リポジトリをフォーク
2. フィーチャーブランチを作成 (`git checkout -b feature/AmazingFeature`)
3. 変更をコミット (`git commit -m 'Add some AmazingFeature'`)
4. ブランチにプッシュ (`git push origin feature/AmazingFeature`)
5. プルリクエストを開く

### 📝 ライセンス

このプロジェクトはMITライセンスの下でライセンスされています。詳細はLICENSEファイルを参照してください。

### 🆘 サポート

サポートについては、以下をご参照ください：
- GitHubでイシューを開く
- 既存のドキュメントを確認
- 開発チームに連絡

### 📞 お問い合わせ

**著者**: tomStory9  
**GitHub**: [@tomStory9](https://github.com/tomStory9)  
**メール**: [ご連絡先]

---

<div align="center">

**Made with ❤️ by tomStory9 & Diodoru**

⭐ If you find this project helpful, please give it a star!

</div>
