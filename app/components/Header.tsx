'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';

export default function Header() {
  const path = usePathname();
  const link = (href: string, label: string) => (
    <Link href={href} className={`nav-link${path === href ? ' active' : ''}`}>
      {label}
    </Link>
  );

  return (
    <header className="site-header">
      <div className="container header-inner">
        <Link href="/" className="brand">
          <span className="brand-mark">VA</span>
          <span>
            <span className="display brand-title">Visión Artificial</span>
            <span className="mono muted">Detección de baches</span>
          </span>
        </Link>
        <nav className="nav">
          {link('/', 'Dashboard')}
          {link('/vias', 'Vías')}
          {link('/detecciones', 'Detecciones')}
        </nav>
      </div>
    </header>
  );
}
