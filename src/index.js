import { createRoot } from 'react-dom/client';
import App from './App';
import './styles.scss';

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('scss-playground-root');
    const root = createRoot(rootElement);
    root.render(<App />);
});
