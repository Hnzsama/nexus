import { defineConfig } from 'vite';
import { resolve } from 'path';
import fs from 'fs';
import tailwindcss from '@tailwindcss/vite';

/**
 * Plugin custom Nexus Framework untuk manajemen file hot reload (public/hot).
 * Menulis URL origin Vite dev server saat aktif dan menghapusnya saat dev server dimatikan.
 */
function nexusHotReloadPlugin() {
  const hotPath = resolve(__dirname, 'public/hot');

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

        // Pastikan folder public ada
        const publicDir = resolve(__dirname, 'public');
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

export default defineConfig({
  publicDir: false,
  plugins: [
    tailwindcss(),
    nexusHotReloadPlugin(),
  ],
  build: {
    outDir: 'public/build',
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
});
