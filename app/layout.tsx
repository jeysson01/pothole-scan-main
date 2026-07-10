import type { Metadata } from 'next';
import Header from './components/Header';
import './globals.css';

export const metadata: Metadata = {
  title: 'Pothole Scan',
  description: 'Detección de baches con visión artificial',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="es">
      <body>
        <Header />
        <main>{children}</main>
        <footer className="site-footer">
          <div className="container footer-inner">
            <span className="mono muted">Pothole Scan · Vercel + Supabase</span>
            <span className="mono muted">YOLOv8 · v1-bache</span>
          </div>
        </footer>
      </body>
    </html>
  );
}
