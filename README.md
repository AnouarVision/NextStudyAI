# NextStudyAI

![PHP](https://img.shields.io/badge/PHP-8.2-%23777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7-%234479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-%2300C853?style=for-the-badge)
![GitHub Repo](https://img.shields.io/badge/GitHub-NextStudyAI-%23181717?style=for-the-badge&logo=github&logoColor=white)

![Homepage NextStudy](/assets/images/index_preview.png)

**NextStudy AI** is a web platform that uses artificial intelligence (Gemini) to transform **uploaded documents** into **interactive and intelligent quizzes** in just seconds. The project is server-side developed in **PHP**, with a simple and modular structure designed to be easily expandable.

---

## Table of Contents

1. [Main Features](#main-features)
2. [Screenshots](#screenshots)
3. [Technologies Used](#technologies-used)
4. [How to Run the Project Locally](#how-to-run-the-project-locally)

---

## Main Features

- **File Upload**: supports `.txt`, `.csv` and `.md` formats up to **10MB**.

- **Automatic AI Quiz Generation**: analyzes documents and generates **personalized questions** based on content.

- **Review and Sharing**: users can modify, save and share generated quizzes.

- **Authentication System**: secure access for each user with automatic redirection to personal dashboard.

---

## Screenshots

### Homepage (`index.php`)
The homepage presents an introductory description and a button that invites the user to start creating their own account.

![Index Screenshot](/assets/images/index_preview.png)

### Authenticated User Dashboard (`dashboard.php`)
After logging in, the user is redirected to the **dashboard**, where they can manage their files, view generated quizzes, and create new ones.

![Dashboard Screenshot](/assets/images/dashboard_preview.png)

### Quiz Configuration with Custom Questions (`dashboard.php`)
Once a file is selected and the continue button is clicked, you will see the Configure Your Quiz page with all customization options.

![Configuration Screenshot](/assets/images/configuration_quiz_preview.png)

---

## Technologies Used

| Component | Description |
|------------|-------------|
| **PHP** | Backend management, server-side logic and user sessions |
| **HTML5 / CSS3** | Structure and styling of the user interface |
| **JavaScript / AJAX** | Dynamic interaction handling (redirects, asynchronous loading, light animations) |
| **MySQL** | Relational database for users, files and quizzes |


---

## How to Run the Project Locally

1. **Clone the repository:**

   ```bash
   git clone https://github.com/your-username/nextstudy.git
   ```

2. **Move the folder:**
   Move the folder to your PHP development environment (for example, to the htdocs directory of XAMPP).

3. **Create a .env file with the necessary credentials:**
   In the project root, create a `.env` file and define your database connection settings and Gemini API key as environment variables:

   ```bash
      # Your database credentials
      DB_HOST=
      DB_USER=
      DB_PASS=
      DB_NAME=

      # Your Gemini API Key
      API_KEY=
   ```
4. **Create the database:**
   Open phpMyAdmin (or any MySQL client) and create a new database

   ```bash
   CREATE DATABASE <yourDBNAME> CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

5. **Copy the SQL script and run it in phpMyAdmin to create the tables, triggers and stored procedures:**
![Script sql](script.sql)

6. **Start the local server:**
   ```bash
   php -S localhost:8000
   ```

7. **Open the project in your browser:**
   ```
   http://localhost:8000/nextstudyai
   ```

---

## Contributing

Feel free to fork the repository, open issues or submit pull requests. Please follow the coding style and comment your code where necessary.

