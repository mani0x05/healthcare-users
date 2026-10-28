import { initializeApp } from
"https://www.gstatic.com/firebasejs/12.19.0/firebase-app.js";

import { getFirestore } from
"https://www.gstatic.com/firebasejs/12.19.0/firebase-firestore.js";

import { getAuth } from
"https://www.gstatic.com/firebasejs/12.19.0/firebase-auth.js";


const firebaseConfig = {
  apiKey: "YOUR_ACTUAL_API_KEY",
  authDomain: "YOUR_ACTUAL_AUTH_DOMAIN",
  projectId: "healthresq-25d4b",
  storageBucket: "YOUR_ACTUAL_STORAGE_BUCKET",
  messagingSenderId: "YOUR_ACTUAL_MESSAGING_SENDER_ID",
  appId: "YOUR_ACTUAL_APP_ID"
};


// Initialize Firebase
const app = initializeApp(firebaseConfig);


// Firestore
const db = getFirestore(app);


// Authentication
const auth = getAuth(app);


// Export so other JS files can use them
export { app, db, auth };