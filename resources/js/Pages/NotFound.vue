<template>
  <div class="not-found-container">
    <!-- Animated Background -->
    <div class="background-animation">
      <div class="floating-shape shape-1"></div>
      <div class="floating-shape shape-2"></div>
      <div class="floating-shape shape-3"></div>
      <div class="floating-shape shape-4"></div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <!-- 404 Number with Glitch Effect -->
      <div class="error-code" :class="{ 'glitch': showGlitch }">
        <span class="glitch-text">404</span>
      </div>

      <!-- Main Message -->
      <h1 class="title">Oops! Page Not Found</h1>
      <p class="subtitle">
        The page you're looking for seems to have vanished into the digital void.
        Don't worry, even the best explorers sometimes take a wrong turn.
      </p>

      <!-- Interactive Buttons -->
      <div class="action-buttons">
        <button @click="goHome" class="btn btn-primary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9,22 9,12 15,12 15,22"/>
          </svg>
          Go Home
        </button>
        <button @click="goBack" class="btn btn-secondary">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15,18 9,12 15,6"/>
          </svg>
          Go Back
        </button>
      </div>

      <!-- Fun Interactive Element -->
      <div class="interactive-element" @click="triggerGlitch">
        <div class="astronaut" :class="{ 'floating': isFloating }">
          🚀
        </div>
        <p class="hint">Click the rocket for a surprise!</p>
      </div>
    </div>

    <!-- Particles Effect -->
    <div class="particles">
      <div v-for="i in 20" :key="i" class="particle" :style="getParticleStyle(i)"></div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFound',
  data() {
    return {
      showGlitch: false,
      isFloating: false,
      particles: []
    }
  },
  mounted() {
    this.startFloatingAnimation()
  },
  methods: {
    goHome() {
      this.$router.push('/')
    },
    goBack() {
      this.$router.go(-1)
    },
    triggerGlitch() {
      this.showGlitch = true
      this.isFloating = !this.isFloating
      setTimeout(() => {
        this.showGlitch = false
      }, 1000)
    },
    startFloatingAnimation() {
      setInterval(() => {
        this.isFloating = !this.isFloating
      }, 3000)
    },
    getParticleStyle(index) {
      const delay = Math.random() * 2
      const duration = 3 + Math.random() * 2
      const size = 2 + Math.random() * 4
      return {
        left: Math.random() * 100 + '%',
        animationDelay: delay + 's',
        animationDuration: duration + 's',
        width: size + 'px',
        height: size + 'px'
      }
    }
  }
}
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.not-found-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  position: relative;
  overflow: hidden;
}

.background-animation {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.floating-shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 6s ease-in-out infinite;
}

.shape-1 {
  width: 80px;
  height: 80px;
  top: 10%;
  left: 10%;
  animation-delay: 0s;
}

.shape-2 {
  width: 60px;
  height: 60px;
  top: 20%;
  right: 10%;
  animation-delay: 1s;
}

.shape-3 {
  width: 100px;
  height: 100px;
  bottom: 10%;
  left: 20%;
  animation-delay: 2s;
}

.shape-4 {
  width: 40px;
  height: 40px;
  bottom: 20%;
  right: 20%;
  animation-delay: 3s;
}

@keyframes float {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(180deg); }
}

.content {
  text-align: center;
  z-index: 2;
  position: relative;
  max-width: 600px;
  padding: 2rem;
}

.error-code {
  font-size: 8rem;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
  margin-bottom: 1rem;
  position: relative;
  display: inline-block;
}

.glitch {
  animation: glitch 0.5s infinite;
}

.glitch .glitch-text {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.glitch .glitch-text:nth-child(2) {
  color: #ff0040;
  animation: glitch-1 0.5s infinite;
}

.glitch .glitch-text:nth-child(3) {
  color: #00ff40;
  animation: glitch-2 0.5s infinite;
}

@keyframes glitch {
  0%, 100% { transform: translate(0); }
  20% { transform: translate(-2px, 2px); }
  40% { transform: translate(-2px, -2px); }
  60% { transform: translate(2px, 2px); }
  80% { transform: translate(2px, -2px); }
}

@keyframes glitch-1 {
  0%, 100% { transform: translate(0); }
  20% { transform: translate(2px, -2px); }
  40% { transform: translate(-2px, 2px); }
  60% { transform: translate(2px, 2px); }
  80% { transform: translate(-2px, -2px); }
}

@keyframes glitch-2 {
  0%, 100% { transform: translate(0); }
  20% { transform: translate(-2px, 2px); }
  40% { transform: translate(2px, -2px); }
  60% { transform: translate(-2px, -2px); }
  80% { transform: translate(2px, 2px); }
}

.title {
  font-size: 3rem;
  color: #fff;
  margin-bottom: 1rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  animation: slideUp 1s ease-out;
}

.subtitle {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 2rem;
  line-height: 1.6;
  animation: slideUp 1s ease-out 0.2s both;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-bottom: 3rem;
  animation: slideUp 1s ease-out 0.4s both;
}

.btn {
  padding: 1rem 2rem;
  border: none;
  border-radius: 50px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  position: relative;
  overflow: hidden;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn-primary {
  background: linear-gradient(45deg, #ff6b6b, #ffa726);
  color: white;
  box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(10px);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.interactive-element {
  animation: slideUp 1s ease-out 0.6s both;
}

.astronaut {
  font-size: 4rem;
  cursor: pointer;
  transition: transform 0.3s ease;
  display: inline-block;
  margin-bottom: 1rem;
}

.astronaut:hover {
  transform: scale(1.1);
}

.astronaut.floating {
  animation: astronautFloat 2s ease-in-out infinite;
}

@keyframes astronautFloat {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-30px) rotate(10deg); }
}

.hint {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.9rem;
  font-style: italic;
}

.particles {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
}

.particle {
  position: absolute;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 50%;
  animation: particleFloat 4s linear infinite;
}

@keyframes particleFloat {
  0% {
    transform: translateY(100vh) rotate(0deg);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-100px) rotate(360deg);
    opacity: 0;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .error-code {
    font-size: 4rem;
  }
  
  .title {
    font-size: 2rem;
  }
  
  .subtitle {
    font-size: 1rem;
  }
  
  .action-buttons {
    flex-direction: column;
    align-items: center;
  }
  
  .btn {
    width: 200px;
    justify-content: center;
  }
  
  .content {
    padding: 1rem;
  }
}
</style>