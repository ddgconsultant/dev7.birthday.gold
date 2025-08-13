# Wayne Test Project

A demonstration project showcasing a basic web application structure with HTML, CSS, JavaScript, and PHP components.

## Project Structure

```
wayne/
├── index.html          # Main HTML page
├── css/               
│   └── styles.css      # Main stylesheet
├── js/
│   └── main.js         # JavaScript functionality
├── api/
│   └── example.php     # Sample PHP API endpoint
├── includes/           # PHP includes directory
├── assets/             # Images and other assets
└── README.md           # This file
```

## Features

- **Responsive Design**: Mobile-first approach with CSS Grid and Flexbox
- **Modern JavaScript**: ES6+ features with async/await support
- **API Ready**: Example PHP API endpoint with JSON responses
- **Interactive UI**: Smooth scrolling, animations, and notifications
- **Clean Architecture**: Organized file structure for scalability

## Getting Started

### Prerequisites

- Web server with PHP support (Apache, Nginx, etc.)
- Modern web browser
- Optional: Node.js for additional tooling

### Installation

1. Clone or copy the project to your web server directory
2. Ensure PHP is enabled for the `/api` directory
3. Open `index.html` in a web browser

### Usage

The main page demonstrates:
- Navigation with smooth scrolling
- Interactive button with notification system
- Responsive feature cards
- API integration ready structure

### API Endpoints

The example API (`/api/example.php`) provides:

- `GET /api/info` - API information
- `GET /api/users` - List of example users
- `GET /api/projects` - List of example projects
- `GET /api/status` - API status check
- `POST /api/users` - Create a new user (simulated)
- `POST /api/projects` - Create a new project (simulated)

### Customization

#### Styling
Edit `css/styles.css` to modify:
- Color scheme (CSS variables in `:root`)
- Layout and spacing
- Component styles

#### Functionality
Edit `js/main.js` to add:
- New interactive features
- API integrations
- Custom animations

#### Backend
Modify `api/example.php` to:
- Connect to a database
- Implement authentication
- Add new endpoints

## Technologies Used

- **HTML5**: Semantic markup
- **CSS3**: Custom properties, Grid, Flexbox
- **JavaScript**: ES6+, Async/await, Intersection Observer
- **PHP**: RESTful API design
- **JSON**: Data exchange format

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## License

This is an example project for demonstration purposes.

## Contributing

Feel free to modify and extend this project for your needs.

## Contact

For questions about this example project, please refer to the main Birthday Gold documentation.