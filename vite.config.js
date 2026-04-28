import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite' // เพิ่มบรรทัดนี้

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(), // เพิ่มบรรทัดนี้ลงใน plugins
  ],
})