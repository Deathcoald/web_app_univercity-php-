users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  prefered_currency VARCHAR(3),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
