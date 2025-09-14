// Advanced Data Visualization Components
class DataVisualizationManager {
    constructor() {
        this.charts = new Map();
        this.initializeAllCharts();
        this.startRealTimeUpdates();
    }

    initializeAllCharts() {
        this.createGPATrendChart();
        this.createAttendanceDonutChart();
        this.createGradeDistributionChart();
        this.createStudyHoursLineChart();
        this.createCoursePerformanceRadar();
        this.createAssignmentBurndownChart();
        this.createLearningProgressHeatmap();
        this.createUpcomingDeadlinesTimeline();
    }

    // GPA Trend Line Chart
    createGPATrendChart() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="section-title">GPA Trend Analysis</h3>
            </div>
            <div class="section-content">
                <div class="gpa-trend-chart" id="gpa-trend-chart">
                    <canvas id="gpaCanvas" width="400" height="200"></canvas>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #667eea;"></div>
                            <span>Semester GPA</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #764ba2;"></div>
                            <span>Cumulative GPA</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.drawGPATrend();
    }

    // Attendance Donut Chart
    createAttendanceDonutChart() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h3 class="section-title">Attendance Breakdown</h3>
            </div>
            <div class="section-content">
                <div class="attendance-donut-chart">
                    <div class="donut-chart-container">
                        <canvas id="attendanceDonut" width="200" height="200"></canvas>
                        <div class="donut-center">
                            <div class="donut-percentage">92%</div>
                            <div class="donut-label">Overall</div>
                        </div>
                    </div>
                    <div class="attendance-stats">
                        <div class="attendance-stat">
                            <div class="stat-color present"></div>
                            <span class="stat-label">Present</span>
                            <span class="stat-value">92%</span>
                        </div>
                        <div class="attendance-stat">
                            <div class="stat-color late"></div>
                            <span class="stat-label">Late</span>
                            <span class="stat-value">5%</span>
                        </div>
                        <div class="attendance-stat">
                            <div class="stat-color absent"></div>
                            <span class="stat-label">Absent</span>
                            <span class="stat-value">3%</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.drawAttendanceDonut();
    }

    // Grade Distribution Chart
    createGradeDistributionChart() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="section-title">Grade Distribution</h3>
            </div>
            <div class="section-content">
                <div class="grade-distribution-chart">
                    <div class="grade-bars">
                        <div class="grade-bar" data-grade="A">
                            <div class="grade-fill" style="height: 85%;"></div>
                            <div class="grade-label">A</div>
                            <div class="grade-count">12</div>
                        </div>
                        <div class="grade-bar" data-grade="B">
                            <div class="grade-fill" style="height: 65%;"></div>
                            <div class="grade-label">B</div>
                            <div class="grade-count">8</div>
                        </div>
                        <div class="grade-bar" data-grade="C">
                            <div class="grade-fill" style="height: 25%;"></div>
                            <div class="grade-label">C</div>
                            <div class="grade-count">3</div>
                        </div>
                        <div class="grade-bar" data-grade="D">
                            <div class="grade-fill" style="height: 10%;"></div>
                            <div class="grade-label">D</div>
                            <div class="grade-count">1</div>
                        </div>
                        <div class="grade-bar" data-grade="F">
                            <div class="grade-fill" style="height: 5%;"></div>
                            <div class="grade-label">F</div>
                            <div class="grade-count">0</div>
                        </div>
                    </div>
                    <div class="grade-insights">
                        <div class="insight-item">
                            <i class="fas fa-trophy" style="color: #ffd700;"></i>
                            <span>Most frequent grade: A</span>
                        </div>
                        <div class="insight-item">
                            <i class="fas fa-arrow-up" style="color: #28a745;"></i>
                            <span>Grade trend: Improving</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.animateGradeBars();
    }

    // Study Hours Line Chart
    createStudyHoursLineChart() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="section-title">Weekly Study Hours</h3>
            </div>
            <div class="section-content">
                <div class="study-hours-chart">
                    <canvas id="studyHoursCanvas" width="400" height="200"></canvas>
                    <div class="study-stats">
                        <div class="study-stat">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">32.5</div>
                                <div class="stat-label">Hours This Week</div>
                            </div>
                        </div>
                        <div class="study-stat">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">4.6</div>
                                <div class="stat-label">Avg Hours/Day</div>
                            </div>
                        </div>
                        <div class="study-stat">
                            <div class="stat-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">7</div>
                                <div class="stat-label">Day Streak</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.drawStudyHoursLine();
    }

    // Course Performance Radar Chart
    createCoursePerformanceRadar() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-radar-chart"></i>
                </div>
                <h3 class="section-title">Course Performance Radar</h3>
            </div>
            <div class="section-content">
                <div class="radar-chart-container">
                    <canvas id="performanceRadar" width="300" height="300"></canvas>
                    <div class="radar-legend">
                        <div class="radar-course">
                            <div class="course-color" style="background: #667eea;"></div>
                            <span>Web Development</span>
                            <span class="course-score">95%</span>
                        </div>
                        <div class="radar-course">
                            <div class="course-color" style="background: #764ba2;"></div>
                            <span>Database Design</span>
                            <span class="course-score">88%</span>
                        </div>
                        <div class="radar-course">
                            <div class="course-color" style="background: #f093fb;"></div>
                            <span>Software Engineering</span>
                            <span class="course-score">82%</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.drawPerformanceRadar();
    }

    // Assignment Burndown Chart
    createAssignmentBurndownChart() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3 class="section-title">Assignment Burndown</h3>
            </div>
            <div class="section-content">
                <div class="burndown-chart">
                    <canvas id="burndownCanvas" width="400" height="200"></canvas>
                    <div class="burndown-stats">
                        <div class="burndown-metric">
                            <div class="metric-value">8</div>
                            <div class="metric-label">Remaining</div>
                        </div>
                        <div class="burndown-metric">
                            <div class="metric-value">15</div>
                            <div class="metric-label">Completed</div>
                        </div>
                        <div class="burndown-metric">
                            <div class="metric-value">3</div>
                            <div class="metric-label">Days Left</div>
                        </div>
                        <div class="burndown-metric">
                            <div class="metric-value">2.7</div>
                            <div class="metric-label">Per Day</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.drawBurndownChart();
    }

    // Learning Progress Heatmap
    createLearningProgressHeatmap() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 class="section-title">Learning Progress Heatmap</h3>
            </div>
            <div class="section-content">
                <div class="learning-heatmap">
                    <div class="heatmap-grid" id="learning-heatmap-grid">
                        <!-- Generated by JavaScript -->
                    </div>
                    <div class="heatmap-labels">
                        <div class="skill-labels">
                            <div class="skill-label">JavaScript</div>
                            <div class="skill-label">React</div>
                            <div class="skill-label">SQL</div>
                            <div class="skill-label">PHP</div>
                            <div class="skill-label">CSS</div>
                        </div>
                        <div class="time-labels">
                            <div class="time-label">Week 1</div>
                            <div class="time-label">Week 2</div>
                            <div class="time-label">Week 3</div>
                            <div class="time-label">Week 4</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.generateLearningHeatmap();
    }

    // Upcoming Deadlines Timeline
    createUpcomingDeadlinesTimeline() {
        const container = document.createElement('div');
        container.className = 'content-section';
        container.innerHTML = `
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 class="section-title">Upcoming Deadlines</h3>
            </div>
            <div class="section-content">
                <div class="deadlines-timeline">
                    <div class="timeline-track">
                        <div class="deadline-item urgent" style="left: 10%;">
                            <div class="deadline-marker"></div>
                            <div class="deadline-info">
                                <div class="deadline-title">React Project</div>
                                <div class="deadline-date">Tomorrow</div>
                                <div class="deadline-course">Web Development</div>
                            </div>
                        </div>
                        <div class="deadline-item warning" style="left: 35%;">
                            <div class="deadline-marker"></div>
                            <div class="deadline-info">
                                <div class="deadline-title">SQL Assignment</div>
                                <div class="deadline-date">3 days</div>
                                <div class="deadline-course">Database Design</div>
                            </div>
                        </div>
                        <div class="deadline-item normal" style="left: 70%;">
                            <div class="deadline-marker"></div>
                            <div class="deadline-info">
                                <div class="deadline-title">Final Project</div>
                                <div class="deadline-date">2 weeks</div>
                                <div class="deadline-course">Software Engineering</div>
                            </div>
                        </div>
                    </div>
                    <div class="timeline-scale">
                        <div class="scale-mark">Today</div>
                        <div class="scale-mark">1 Week</div>
                        <div class="scale-mark">2 Weeks</div>
                        <div class="scale-mark">1 Month</div>
                    </div>
                </div>
            </div>
        `;
        
        this.insertChartSection(container);
        this.animateTimeline();
    }

    // Helper method to insert chart sections
    insertChartSection(container) {
        const contentGrid = document.querySelector('.content-grid');
        if (contentGrid) {
            contentGrid.appendChild(container);
        }
    }

    // Drawing methods for canvas charts
    drawGPATrend() {
        const canvas = document.getElementById('gpaCanvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const data = [3.2, 3.4, 3.6, 3.5, 3.7, 3.75];
        const cumulative = [3.2, 3.3, 3.4, 3.42, 3.51, 3.55];
        
        this.drawLineChart(ctx, canvas, data, cumulative, ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6']);
    }

    drawAttendanceDonut() {
        const canvas = document.getElementById('attendanceDonut');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const data = [92, 5, 3]; // Present, Late, Absent
        const colors = ['#28a745', '#ffc107', '#dc3545'];
        
        this.drawDonutChart(ctx, canvas, data, colors);
    }

    drawStudyHoursLine() {
        const canvas = document.getElementById('studyHoursCanvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const data = [3.5, 4.2, 2.8, 5.1, 3.9, 6.2, 4.5];
        
        this.drawStudyLineChart(ctx, canvas, data, ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']);
    }

    drawPerformanceRadar() {
        const canvas = document.getElementById('performanceRadar');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const categories = ['Assignments', 'Exams', 'Participation', 'Projects', 'Attendance'];
        const scores = [95, 88, 92, 87, 96];
        
        this.drawRadarChart(ctx, canvas, categories, scores);
    }

    drawBurndownChart() {
        const canvas = document.getElementById('burndownCanvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const ideal = [23, 18, 14, 10, 6, 3, 0];
        const actual = [23, 19, 15, 12, 8, 8];
        
        this.drawBurndownLineChart(ctx, canvas, ideal, actual);
    }

    // Animation methods
    animateGradeBars() {
        const bars = document.querySelectorAll('.grade-fill');
        bars.forEach((bar, index) => {
            setTimeout(() => {
                bar.style.transition = 'height 1s ease-out';
                bar.style.height = bar.style.height;
            }, index * 200);
        });
    }

    generateLearningHeatmap() {
        const grid = document.getElementById('learning-heatmap-grid');
        if (!grid) return;
        
        const skills = 5;
        const weeks = 4;
        
        for (let week = 0; week < weeks; week++) {
            for (let skill = 0; skill < skills; skill++) {
                const cell = document.createElement('div');
                cell.className = 'heatmap-cell';
                
                const intensity = Math.random();
                if (intensity > 0.7) cell.classList.add('high');
                else if (intensity > 0.4) cell.classList.add('medium');
                else cell.classList.add('low');
                
                cell.style.animationDelay = `${(week * skills + skill) * 50}ms`;
                grid.appendChild(cell);
            }
        }
    }

    animateTimeline() {
        const items = document.querySelectorAll('.deadline-item');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.animation = 'fadeInUp 0.6s ease-out forwards';
            }, index * 200);
        });
    }

    // Canvas drawing utilities
    drawLineChart(ctx, canvas, data1, data2, labels) {
        const width = canvas.width;
        const height = canvas.height;
        const padding = 40;
        
        ctx.clearRect(0, 0, width, height);
        
        // Draw grid
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
        ctx.lineWidth = 1;
        
        for (let i = 0; i <= 5; i++) {
            const y = padding + (height - 2 * padding) * i / 5;
            ctx.beginPath();
            ctx.moveTo(padding, y);
            ctx.lineTo(width - padding, y);
            ctx.stroke();
        }
        
        // Draw lines
        this.drawDataLine(ctx, data1, '#667eea', width, height, padding);
        this.drawDataLine(ctx, data2, '#764ba2', width, height, padding);
    }

    drawDataLine(ctx, data, color, width, height, padding) {
        const maxValue = 4.0;
        const stepX = (width - 2 * padding) / (data.length - 1);
        
        ctx.strokeStyle = color;
        ctx.lineWidth = 3;
        ctx.beginPath();
        
        data.forEach((value, index) => {
            const x = padding + index * stepX;
            const y = height - padding - (value / maxValue) * (height - 2 * padding);
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Draw points
        ctx.fillStyle = color;
        data.forEach((value, index) => {
            const x = padding + index * stepX;
            const y = height - padding - (value / maxValue) * (height - 2 * padding);
            
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, 2 * Math.PI);
            ctx.fill();
        });
    }

    drawDonutChart(ctx, canvas, data, colors) {
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 80;
        const innerRadius = 50;
        
        let currentAngle = -Math.PI / 2;
        const total = data.reduce((sum, value) => sum + value, 0);
        
        data.forEach((value, index) => {
            const sliceAngle = (value / total) * 2 * Math.PI;
            
            // Draw outer arc
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
            ctx.arc(centerX, centerY, innerRadius, currentAngle + sliceAngle, currentAngle, true);
            ctx.closePath();
            ctx.fillStyle = colors[index];
            ctx.fill();
            
            currentAngle += sliceAngle;
        });
    }

    drawRadarChart(ctx, canvas, categories, scores) {
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 100;
        const sides = categories.length;
        const angleStep = (2 * Math.PI) / sides;
        
        // Draw grid
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
        ctx.lineWidth = 1;
        
        for (let level = 1; level <= 5; level++) {
            ctx.beginPath();
            for (let i = 0; i <= sides; i++) {
                const angle = i * angleStep - Math.PI / 2;
                const x = centerX + Math.cos(angle) * radius * level / 5;
                const y = centerY + Math.sin(angle) * radius * level / 5;
                
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            }
            ctx.closePath();
            ctx.stroke();
        }
        
        // Draw data
        ctx.fillStyle = 'rgba(102, 126, 234, 0.3)';
        ctx.strokeStyle = '#667eea';
        ctx.lineWidth = 2;
        
        ctx.beginPath();
        scores.forEach((score, index) => {
            const angle = index * angleStep - Math.PI / 2;
            const distance = (score / 100) * radius;
            const x = centerX + Math.cos(angle) * distance;
            const y = centerY + Math.sin(angle) * distance;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.closePath();
        ctx.fill();
        ctx.stroke();
    }

    // Real-time updates
    startRealTimeUpdates() {
        setInterval(() => {
            this.updateChartData();
        }, 5000);
    }

    updateChartData() {
        // Update GPA circle
        const gpaCircle = document.querySelector('.gpa-circle');
        if (gpaCircle) {
            const newGpa = (3.5 + Math.random() * 0.5).toFixed(2);
            const percentage = (newGpa / 4.0) * 100;
            gpaCircle.style.setProperty('--gpa-percentage', percentage);
            
            const gpaValue = document.querySelector('.gpa-value-center .value');
            if (gpaValue) {
                gpaValue.textContent = newGpa;
            }
        }
        
        // Update attendance bars
        document.querySelectorAll('.attendance-bar').forEach(bar => {
            const newHeight = Math.floor(Math.random() * 50) + 50;
            bar.style.height = `${newHeight}px`;
        });
        
        // Update progress bars
        document.querySelectorAll('.assignment-progress-fill').forEach(bar => {
            const newProgress = Math.floor(Math.random() * 30) + 50;
            bar.style.width = `${newProgress}%`;
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dataVisualization = new DataVisualizationManager();
});
