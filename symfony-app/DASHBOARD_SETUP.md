# Vue.js Dashboard Setup

The Vue.js dashboard needs to be built or run via Vite dev server.

## Option 1: Run Vite Dev Server (Recommended for Development)

1. Install dependencies:
```bash
cd symfony-app
npm install
# or
pnpm install
```

2. Start the Vite dev server:
```bash
npm run dev
# or
pnpm dev
```

3. Access the dashboard at: **http://localhost:3001**

The dev server will proxy API requests to `http://localhost:7849/api`

## Option 2: Build for Production

1. Install dependencies:
```bash
cd symfony-app
npm install
```

2. Build the Vue app:
```bash
npm run build
```

3. The built files will be in `public/build/`

4. Access the dashboard at: **http://localhost:7849/**

## Current Status

- ✅ Symfony API is running at http://localhost:7849/api
- ✅ Adminer is running at http://localhost:9090
- ⚠️ Vue.js dashboard needs to be built or run via dev server

## Quick Start

To get the dashboard running quickly:

```bash
cd symfony-app
npm install
npm run dev
```

Then open http://localhost:3001 in your browser.

