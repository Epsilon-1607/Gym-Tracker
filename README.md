# Gym-Tracker
A minimalist, zero-database habit and workout tracker built with PHP. Features an automated penalty system to keep you disciplined.

=========Arc Tracker - Minimalist Habit & Workout Tracker=========

This is a single-file PHP web app I built to track my workout routines and daily habits. The design is intentionally minimalist with a black-and-white theme, focused purely on functionality, and very mobile-friendly. The best part is it doesn't need any database setup at all.

Here is how it works:

Automatic 7-Day Cycle
It loops through a 7-day schedule like Push, Pull, Legs, and Rest. If you want to change your routine, you can edit it directly using the Edit button in the app.

Auto-Violation Log
This is the core feature. If you forget to check a mandatory task (like skipping a workout or reading) and complete the day, the app automatically logs it as a penalty in the Violation Log.

Rest Day Redemption
You can't really rest on a Rest Day if you have pending violations. The app will force a punishment prompt on you, like doing extra physical reps or a strict diet. Once you select and execute the punishment, your log is cleared.

Statistics Page
There is a page to track your progress. You can see what week you are on, how many flawless days you have had with zero violations, and the total penalties you have redeemed.

3D Slide Navigation
Even though it is a web app, it feels like a native mobile app. You can switch pages left and right with a smooth page-turning effect. It also remembers your current page, so it doesn't throw you back to the start every time you reload.

Built with:

* Pure PHP for the backend.
* HTML, Vanilla JavaScript, and Tailwind CSS via CDN for the frontend.
* Local JSON file for data storage.

How to run it:
Since it uses PHP, you will need a local server like XAMPP, MAMP, or Laragon if you want to run it on your computer.

1. Download the index.php file.
2. Put it in your htdocs (XAMPP) or www (Laragon) folder.
3. Open your browser and go to localhost/your-folder-name.
4. That is it. The JSON data file will be created automatically when you load the app for the first time. Just make sure the folder has write permissions.

You can also turn it into a mobile app. Just add a manifest.json file, link it in the HTML, host it online, open it on your phone browser, and tap Add to Home Screen.
