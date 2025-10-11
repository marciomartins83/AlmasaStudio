// Importa estilos globais (SCSS com Bootstrap)
import './styles/app.scss';

// Importa Bootstrap 5 (JS)
import 'bootstrap';

// Importa Stimulus
import { Application } from '@hotwired/stimulus';

// Inicializa Stimulus
const application = Application.start();

// Log para debug
console.log('%c[ALMASA] Frontend iniciado com sucesso ✅', 'color: #28a745; font-weight: bold;');
