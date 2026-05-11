<style>
  .prime-preloader {
    position: fixed;
    inset: 0;
    z-index: 12345;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
      linear-gradient(135deg, rgba(245, 248, 255, 0.96), rgba(255, 255, 255, 0.98));
  }

  .prime-preloader-box {
    width: min(92vw, 360px);
    padding: 28px;
    text-align: center;
  }

  .prime-preloader-logo {
    width: 112px;
    height: 112px;
    margin: 0 auto 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #ffffff;
    box-shadow: 0 16px 38px rgba(16, 24, 40, 0.16);
  }

  .prime-preloader-logo img {
    width: 96px;
    height: 96px;
    object-fit: contain;
  }

  .prime-preloader-title {
    margin: 0 0 4px;
    color: #155eef;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 0;
  }

  .prime-preloader-subtitle {
    margin: 0 0 18px;
    color: #667085;
    font-size: 13px;
    font-weight: 600;
  }

  .prime-preloader-progress {
    height: 8px;
    overflow: hidden;
    border-radius: 999px;
    background: #e4e7ec;
  }

  .prime-preloader .prime-preloader-progress .bar {
    display: block;
    width: 42%;
    height: 100%;
    border-radius: inherit;
    background: #155eef;
    animation: primePreloader 1.05s ease-in-out infinite;
  }

  .prime-preloader-text {
    margin-top: 14px;
    color: #475467;
    font-size: 13px;
    font-weight: 700;
  }

  @keyframes primePreloader {
    0% { transform: translateX(-120%); }
    100% { transform: translateX(260%); }
  }
</style>

<div class="pre-loader prime-preloader">
  <div class="prime-preloader-box">
    <div class="prime-preloader-logo">
      <img src="../assets_pang1/logo.png" alt="SDO 1 Pangasinan Logo">
    </div>
    <p class="prime-preloader-title">PRIMEHR</p>
    <p class="prime-preloader-subtitle">Schools Division Office 1 Pangasinan</p>
    <div class="prime-preloader-progress" id="progress_div">
      <span class="bar" id="bar1"></span>
    </div>
    <div class="percent" id="percent1">0%</div>
    <div class="prime-preloader-text loading-text">Loading...</div>
  </div>
</div>
