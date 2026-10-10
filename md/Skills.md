# Technical Specifications and System Architecture: LifeTrack

This document is the technical derivative of the LifeTrack Product Requirements Document (PRD), detailing the system's background operations, application architecture, and database structure designed to support the MVP features.

## 1. System Architecture and Workflow

The LifeTrack system utilizes a standard Client-Server architecture with background processing to handle task scheduling, notifications, and streak calculations.

### Core Component Workflow:
*   **Client App (iOS/Android):** Sends user interactions (login, habit check-ins, journal uploads) to the server via RESTful APIs. It caches data using local storage (such as SQLite/Room) to ensure faster loading times (offline-first approach).
*   **Backend Server:** Validates data, executes business logic (e.g., calculating completion percentages, validating image sizes), and saves data to the database.
*   **Cloud Storage:** Manages media files (journal photos). When a user uploads a photo, the backend uploads it to Cloud Storage (e.g., AWS S3 or Firebase Storage) and stores only the image URL in the database.
*   **Cron Jobs / Worker:** Automated background scripts that run hourly to check user time zones. If the user's local time is 08:00 (for habits) or 20:00 (for journals), the worker triggers Firebase Cloud Messaging (FCM) to send push notifications.

## 2. Recommended Tech Stack

*   **Frontend (Mobile App):** Flutter or React Native (A single codebase for iOS & Android accelerates MVP development).
*   **Backend:** **Laravel 12 (PHP)** (Excellent for rapidly building robust RESTful APIs, handling background jobs with Laravel Queues, and providing built-in task scheduling via Laravel Scheduler).
*   **Main Database:** PostgreSQL (A relational database is highly suitable for managing the relationships between users, habits, and journals).
*   **Cloud Storage & Push Notifications:** Firebase Cloud Storage (for images) and Firebase Cloud Messaging (for notifications).
*   **Hosting / Infrastructure:** AWS (EC2 & RDS) or PaaS services like Laravel Forge, Render, or Railway for the MVP phase.

## 3. Database Schema Design (Entity Relationship)

The system will use a Relational Database approach. Below is the proposed design for tables, columns, and relationships required for the LifeTrack MVP.

### Table: `users`
Stores user authentication data and basic preferences.

| Column Name | Data Type | Description | Attributes |
| :--- | :--- | :--- | :--- |
| `id` | UUID | Unique identifier (Primary Key) | PK, Not Null |
| `email` | Varchar | Login email | Unique, Not Null |
| `password_hash` | Varchar | Encrypted password | Not Null |
| `timezone` | Varchar | Time zone (e.g., "Asia/Jakarta") for notifications | Not Null |
| `created_at` | Timestamp | Account creation date | Default: NOW() |

### Table: `habits`
Stores the list of main habits created by the user.

| Column Name | Data Type | Description | Attributes |
| :--- | :--- | :--- | :--- |
| `id` | UUID | Unique habit identifier | PK, Not Null |
| `user_id` | UUID | Habit owner | FK -> `users.id` |
| `title` | Varchar | Habit name (e.g., "Drink Water") | Not Null |
| `is_active` | Boolean | Active/Deleted status (Soft delete) | Default: True |
| `created_at` | Timestamp | Time habit was added | Default: NOW() |

### Table: `habit_logs`
Records every instance a user checks/completes a habit. This table is crucial for the Analytics and Streak features.

| Column Name | Data Type | Description | Attributes |
| :--- | :--- | :--- | :--- |
| `id` | UUID | Log identifier | PK, Not Null |
| `habit_id` | UUID | Completed habit | FK -> `habits.id` |
| `user_id` | UUID | User who completed the habit | FK -> `users.id` |
| `log_date` | Date | Log date (without time) for daily queries | Not Null |
| `completed_at`| Timestamp | Exact time the habit was checked | Not Null |

*(Note: The combination of `habit_id` and `log_date` must be UNIQUE so users cannot check the same habit twice on the same day).*

### Table: `journals`
Stores daily journal entries, mood scores, and image references (Day-in-My-Life).

| Column Name | Data Type | Description | Attributes |
| :--- | :--- | :--- | :--- |
| `id` | UUID | Journal entry identifier | PK, Not Null |
| `user_id` | UUID | Journal author | FK -> `users.id` |
| `entry_date` | Date | Date the journal was written | Not Null |
| `content` | Text | Journal text content | Not Null |
| `mood_score` | Integer | Mood scale (1-5) | Not Null |
| `image_url` | Varchar | Image link in Cloud Storage | Nullable |
| `created_at` | Timestamp | Time the journal was initially saved | Default: NOW() |
| `updated_at` | Timestamp | Journal revision time | Nullable |

*(Note: The combination of `user_id` and `entry_date` is UNIQUE since the system limits users to writing 1 main journal entry per day, aligning with the Day-in-My-Life concept).*

### Table: `user_streaks`
Stores user achievement calculations so the backend doesn't have to recalculate (query sum) from the `habit_logs` table every time the app is opened.

| Column Name | Data Type | Description | Attributes |
| :--- | :--- | :--- | :--- |
| `user_id` | UUID | User identifier | PK, FK -> `users.id` |
| `current_streak`| Integer | Current consecutive active days | Default: 0 |
| `longest_streak`| Integer | Highest record ever achieved | Default: 0 |
| `last_active_date`| Date | Last date a habit/journal was completed | Nullable |

## 4. Key Business Logic Mechanisms

**Streak Calculation Logic:**
1. When the user checks their first habit of the day, the system checks `last_active_date` in the `user_streaks` table.
2. If `last_active_date` is **yesterday** (D-1), `current_streak` increases by 1 (+1).
3. If `last_active_date` is **today**, `current_streak` remains unchanged.
4. If `last_active_date` is older than yesterday (D-2 or more), `current_streak` is reset to 1.
5. If `current_streak` surpasses the `longest_streak`, the system updates the `longest_streak`.

**Weekly Analytics Logic:**
To display charts in the Analytics tab, the system will perform a *Join Query* between the `habit_logs` and `journals` tables based on `user_id` and a date range (the last 7 days). This enables the application to display correlations, for example: *"On Tuesdays and Wednesdays when all habits are completed, your mood_score averages at 5."*
