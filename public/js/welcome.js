// welcome.js - GSAP ScrollTrigger Orchestration
document.addEventListener('DOMContentLoaded', () => {
    // Make sure GSAP is loaded
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

    gsap.registerPlugin(ScrollTrigger);

    // 1. Initial Load Animation (Hero Reveal)
    const tlLoad = gsap.timeline();
    
    // Nav pill
    tlLoad.to('.gs-nav-pill', {
        y: 0,
        opacity: 1,
        duration: 1,
        ease: 'power3.out',
        delay: 0.2
    });

    // Hero Text Lines
    tlLoad.fromTo('.gs-hero-text .gs-line', 
        { y: 100, rotation: 2, opacity: 0 },
        { y: 0, rotation: 0, opacity: 1, duration: 1.2, stagger: 0.15, ease: 'power4.out' },
        '-=0.8'
    );

    // Hero Fades (Paragraph & buttons)
    tlLoad.fromTo('.gs-hero-text .gs-fade',
        { y: 30, opacity: 0 },
        { y: 0, opacity: 1, duration: 1, stagger: 0.2, ease: 'power3.out' },
        '-=0.8'
    );

    // Hero Image Clip Path Reveal
    tlLoad.fromTo('.gs-hero-img .editorial-img',
        { clipPath: 'inset(100% 0% 0% 0%)', scale: 1.1 },
        { clipPath: 'inset(0% 0% 0% 0%)', scale: 1, duration: 1.5, ease: 'power4.inOut' },
        '-=1.2'
    );

    tlLoad.fromTo('.gs-hero-badge',
        { y: 50, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.8, ease: 'back.out(1.5)' },
        '-=0.5'
    );

    // 2. Continuous Background Blobs Animation (Smooth organic movement)
    const blobs = document.querySelectorAll('.blob-gsap');
    blobs.forEach((blob, i) => {
        gsap.to(blob, {
            x: 'random(-100, 100)',
            y: 'random(-100, 100)',
            scale: 'random(0.8, 1.2)',
            rotation: 'random(-45, 45)',
            duration: 'random(10, 20)',
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true,
            delay: i * -2
        });
    });

    // 3. Ticker Parallax (Moves horizontally based on scroll)
    gsap.to('.gs-ticker-track', {
        xPercent: -30,
        ease: 'none',
        scrollTrigger: {
            trigger: '.gs-ticker-section',
            start: 'top bottom',
            end: 'bottom top',
            scrub: 1
        }
    });

    // 4. Features Section Reveal (Bento Cards)
    const tlFeat = gsap.timeline({
        scrollTrigger: {
            trigger: '#fitur',
            start: 'top 75%',
            end: 'top 25%',
            toggleActions: 'play none none reverse'
        }
    });

    tlFeat.fromTo('.gs-feat-title .gs-line',
        { y: 80, opacity: 0 },
        { y: 0, opacity: 1, duration: 1, stagger: 0.1, ease: 'power3.out' }
    );

    tlFeat.fromTo('.gs-card',
        { y: 100, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: 'power3.out' },
        '-=0.6'
    );

    // Parallax on card images
    gsap.utils.toArray('.gs-parallax-img').forEach(imgWrap => {
        gsap.to(imgWrap, {
            yPercent: 15,
            ease: 'none',
            scrollTrigger: {
                trigger: imgWrap.parentElement,
                start: 'top bottom',
                end: 'bottom top',
                scrub: true
            }
        });
    });

    // 5. How It Works (Sticky & Step Highlighting like protection.gr)
    const steps = gsap.utils.toArray('.gs-step');
    
    // Title reveal for "Cara Kerja"
    gsap.fromTo('.gs-cara-left .gs-line',
        { y: 80, opacity: 0 },
        {
            y: 0, opacity: 1, duration: 1, stagger: 0.1, ease: 'power3.out',
            scrollTrigger: {
                trigger: '#cara',
                start: 'top 60%',
            }
        }
    );

    // Highlight steps as they scroll into center of screen
    steps.forEach((step, i) => {
        ScrollTrigger.create({
            trigger: step,
            start: 'top 55%',
            end: 'bottom 55%',
            toggleClass: 'is-active',
            // markers: false
        });
        
        // Also fade them in originally
        gsap.fromTo(step, 
            { x: 50, opacity: 0 },
            { 
                x: 0, opacity: 1, duration: 0.8, ease: 'power3.out',
                scrollTrigger: {
                    trigger: step,
                    start: 'top 85%'
                }
            }
        );
    });

    // 6. CTA & Footer Reveal
    const tlCta = gsap.timeline({
        scrollTrigger: {
            trigger: '.gs-footer-wrap',
            start: 'top 80%',
        }
    });

    tlCta.fromTo('.gs-cta .gs-line',
        { y: 80, opacity: 0 },
        { y: 0, opacity: 1, duration: 1, stagger: 0.1, ease: 'power3.out' }
    );
    tlCta.fromTo('.gs-cta .gs-fade',
        { y: 30, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.8, stagger: 0.15, ease: 'power3.out' },
        '-=0.6'
    );

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                // If using GSAP scrollTo plugin, could use that, else native:
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
