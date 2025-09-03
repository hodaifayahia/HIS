
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clinic Oasis - HIS</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="icon" type="image/png" href="{{ asset('login.png') }}">
  
  <!-- Prevent FOUC (Flash of Unstyled Content) -->
  <style>
    #app {
      min-height: 100vh;
    }
    
    /* Loading skeleton for better perceived performance */
    .app-loading {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .loading-pulse {
      width: 60px;
      height: 60px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid white;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Hide loading once Vue app is mounted */
    #app:not(:empty) + .app-loading {
      display: none;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini text-sm">
  <!-- Single app mount point for unified SPA -->
  <div id="app"></div>
  
  <!-- Fallback loading indicator while Vue app initializes -->
  <div class="app-loading">
    <div>
      <div class="loading-pulse"></div>
      <p style="color: white; margin-top: 1rem; text-align: center;">Loading HIS...</p>
    </div>
  </div>

  <!-- Sidebar state management script -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const body = document.querySelector('body');
      
      // Load saved sidebar state on page load
      const savedState = localStorage.getItem('sidebarState');
      if (savedState === 'collapsed') {
        body.classList.add('sidebar-collapse');
      }

      // Handle sidebar toggle (delegated event listening for dynamic content)
      document.addEventListener('click', function(e) {
        const sidebarToggle = e.target.closest('[data-widget="pushmenu"]');
        if (sidebarToggle) {
          e.preventDefault();
          e.stopPropagation();
          
          // Toggle the class
          body.classList.toggle('sidebar-collapse');
          
          // Save the new state
          const isCollapsed = body.classList.contains('sidebar-collapse');
          localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
        }
      });
    });
  </script>
  
  <!-- Tailwind Configuration -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      prefix: "tw-",
      corePlugins: {
        preflight: false,
      }
    }
  </script>
</body>
</html>
