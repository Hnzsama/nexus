import { defineConfig, loadEnv } from 'vite';
import { resolve } from 'path';
import fs from 'fs';
import tailwindcss from '@tailwindcss/vite';

/**
 * Plugin custom Nexus Framework untuk manajemen file hot reload (public/hot atau ../hot).
 * Menulis URL origin Vite dev server saat aktif dan menghapusnya saat dev server dimatikan.
 */
function nexusHotReloadPlugin(publicDir) {
  const hotPath = resolve(publicDir, 'hot');

  return {
    name: 'nexus-hot-reload-plugin',
    configureServer(server) {
      const writeHotFile = () => {
        const address = server.httpServer?.address();
        const host = typeof address === 'object' && address && address.address !== '::' && address.address !== '0.0.0.0'
          ? address.address
          : 'localhost';
        const port = typeof address === 'object' && address ? address.port : 5173;
        const protocol = server.config.server.https ? 'https' : 'http';
        const origin = `${protocol}://${host}:${port}`;

        // Pastikan direktori public ada sebelum menulis file marker
        if (!fs.existsSync(publicDir)) {
          fs.mkdirSync(publicDir, { recursive: true });
        }

        fs.writeFileSync(hotPath, origin);
      };

      const removeHotFile = () => {
        if (fs.existsSync(hotPath)) {
          try {
            fs.unlinkSync(hotPath);
          } catch {
            // Abaikan jika sudah terhapus
          }
        }
      };

      server.httpServer?.once('listening', writeHotFile);

      process.on('exit', removeHotFile);
      process.on('SIGINT', () => {
        removeHotFile();
        process.exit();
      });
      process.on('SIGTERM', () => {
        removeHotFile();
        process.exit();
      });
      process.on('SIGHUP', () => {
        removeHotFile();
        process.exit();
      });
    },
  };
}

export default defineConfig(({ mode }) => {
  // Muat variabel environment dari .env di root proyek
  const env = loadEnv(mode, process.cwd(), '');
  const isAaPanel = env.IS_AAPANEL === 'true' || env.IS_AAPANEL === '1';

  // Tentukan path direktori public secara dinamis:
  // 1. Jika PUBLIC_PATH didefinisikan, gunakan direktori tersebut
  // 2. Jika IS_AAPANEL=true, arahkan 1 tingkat ke luar folder proyek (..)
  // 3. Mode default: folder 'public' di dalam proyek
  let publicDir = resolve(__dirname, 'public');
  if (env.PUBLIC_PATH) {
    publicDir = resolve(__dirname, env.PUBLIC_PATH);
  } else if (isAaPanel) {
    publicDir = resolve(__dirname, '..');
  }

  const outDir = resolve(publicDir, 'build');

  return {
    publicDir: false,
    plugins: [
      tailwindcss(),
      nexusHotReloadPlugin(publicDir),
    ],
    build: {
      outDir: outDir,
      emptyOutDir: true,
      manifest: true,
      rollupOptions: {
        input: [
          'resources/css/app.css',
          'resources/js/app.js',
        ],
      },
    },
    server: {
      host: 'localhost',
      port: 5173,
      strictPort: true,
      origin: 'http://localhost:5173',
      cors: true,
      hmr: {
        host: 'localhost',
      },
    },
  };
});
