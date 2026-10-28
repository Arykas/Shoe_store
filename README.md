<div align="center">

# Habit Tracker

**A simple app to track your daily habits (e.g., drink water, read 10 pages).**

<img src="path/to/demo.gif" alt="Habit Tracker demo" width="600" />

</div>

---

## ✨ Features

- Create, edit, and delete daily habits.
- Mark habits as completed for the current day.
- Track current and longest streaks per habit.
- View a simple weekly progress overview.
- Store all data locally in a lightweight database.

---

## 🚀 Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourname/habit-tracker.git
   cd habit-tracker
   ```

2. Create a virtual environment and install dependencies:
   ```bash
   python -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   pip install -r requirements.txt
   ```

3. Run the app:
   ```bash
   python app.py
   ```
   Then open `http://localhost:5000` in your browser.

---

## 💡 Usage

- **Add a habit:** Open the app, click “New Habit”, enter a name such as “Drink 8 glasses of water”, and save it.
- **Complete a habit:** On the dashboard, click the checkbox next to a habit to mark it done for today; your streak updates automatically.

---

## 🛠️ Built With

| Technology | Purpose |
|------------|---------|
| Python | Core application logic |
| Flask | Web framework |
| SQLite | Local data storage |
| HTML/CSS + Jinja2 | Templates and UI |

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.
