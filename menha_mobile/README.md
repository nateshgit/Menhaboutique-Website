# Menha Boutique Mobile App

This is the React Native (Expo) application for Menha Boutique customers to order products.

## Setup

1.  Navigate to the directory:
    ```bash
    cd MenhaMobile
    ```

2.  Install dependencies:
    ```bash
    npm install
    ```
    If `npm install` fails, try deleting `node_modules` and `package-lock.json` and running `npm install` again, or use `yarn`.

3.  Start the app:
    ```bash
    npx expo start
    ```

## Configuration

- **API URL**: Check `src/services/api.ts` to ensure the `API_URL` points to your running backend (e.g., `http://10.0.2.2:5000/api` for Android Emulator or your public domain).

## Features

- **Login**: Customer login (Admins are restricted to Web Panel, but login logic is flexible).
- **Product List**: Browse products.
- **Product Details**: View product info.
- **Cart**: View cart (mocked implementation ready for API integration).

## Tech Stack

- React Native
- Expo
- TypeScript
- React Navigation
- Axios
