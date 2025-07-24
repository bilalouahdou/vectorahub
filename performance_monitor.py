#!/usr/bin/env python3
"""
Core Web Vitals Performance Monitor
Monitors LCP, INP, CLS and sends alerts when thresholds are exceeded
"""

import requests
import json
import smtplib
import time
from email.mime.text import MimeText
from email.mime.multipart import MimeMultipart
from datetime import datetime
import os
from typing import Dict, List

class PerformanceMonitor:
    def __init__(self):
        self.config = {
            'urls_to_monitor': [
                'https://vectorizeai.com/',
                'https://vectorizeai.com/pricing',
                'https://vectorizeai.com/blog/',
                'https://vectorizeai.com/dashboard'
            ],
            'thresholds': {
                'lcp': 2.5,  # seconds
                'inp': 200,  # milliseconds
                'cls': 0.1   # cumulative layout shift
            },
            'email': {
                'smtp_server': 'smtp.gmail.com',
                'smtp_port': 587,
                'sender_email': os.getenv('MONITOR_EMAIL'),
                'sender_password': os.getenv('MONITOR_EMAIL_PASSWORD'),
                'recipient_email': os.getenv('ALERT_EMAIL')
            },
            'discord_webhook': os.getenv('DISCORD_WEBHOOK_URL'),
            'pagespeed_api_key': os.getenv('PAGESPEED_API_KEY')
        }
    
    def get_pagespeed_metrics(self, url: str) -> Dict:
        """Get Core Web Vitals from PageSpeed Insights API"""
        api_url = f"https://www.googleapis.com/pagespeedonline/v5/runPagespeed"
        params = {
            'url': url,
            'key': self.config['pagespeed_api_key'],
            'category': 'performance',
            'strategy': 'mobile'
        }
        
        try:
            response = requests.get(api_url, params=params, timeout=60)
            response.raise_for_status()
            data = response.json()
            
            # Extract Core Web Vitals
            lighthouse_result = data.get('lighthouseResult', {})
            audits = lighthouse_result.get('audits', {})
            
            metrics = {
                'url': url,
                'timestamp': datetime.now().isoformat(),
                'lcp': self._extract_metric(audits, 'largest-contentful-paint'),
                'inp': self._extract_metric(audits, 'max-potential-fid'),  # Proxy for INP
                'cls': self._extract_metric(audits, 'cumulative-layout-shift'),
                'performance_score': lighthouse_result.get('categories', {}).get('performance', {}).get('score', 0) * 100
            }
            
            return metrics
            
        except Exception as e:
            print(f"Error fetching metrics for {url}: {str(e)}")
            return None
    
    def _extract_metric(self, audits: Dict, metric_key: str) -> float:
        """Extract specific metric value from PageSpeed audits"""
        metric_data = audits.get(metric_key, {})
        if 'numericValue' in metric_data:
            value = metric_data['numericValue']
            # Convert to appropriate units
            if metric_key == 'largest-contentful-paint':
                return value / 1000  # Convert to seconds
            elif metric_key == 'max-potential-fid':
                return value  # Already in milliseconds
            elif metric_key == 'cumulative-layout-shift':
                return value  # Already in correct unit
        return 0.0
    
    def check_thresholds(self, metrics: Dict) -> List[str]:
        """Check if metrics exceed thresholds and return violations"""
        violations = []
        
        if metrics['lcp'] > self.config['thresholds']['lcp']:
            violations.append(f"LCP: {metrics['lcp']:.2f}s (threshold: {self.config['thresholds']['lcp']}s)")
        
        if metrics['inp'] > self.config['thresholds']['inp']:
            violations.append(f"INP: {metrics['inp']:.0f}ms (threshold: {self.config['thresholds']['inp']}ms)")
        
        if metrics['cls'] > self.config['thresholds']['cls']:
            violations.append(f"CLS: {metrics['cls']:.3f} (threshold: {self.config['thresholds']['cls']})")
        
        return violations
    
    def send_email_alert(self, url: str, violations: List[str], metrics: Dict):
        """Send email alert for performance violations"""
        try:
            msg = MimeMultipart()
            msg['From'] = self.config['email']['sender_email']
            msg['To'] = self.config['email']['recipient_email']
            msg['Subject'] = f"🚨 Performance Alert: {url}"
            
            body = f"""
            Performance Alert for VectorizeAI
            
            URL: {url}
            Timestamp: {metrics['timestamp']}
            Performance Score: {metrics['performance_score']:.1f}/100
            
            Violations:
            {chr(10).join(f"• {violation}" for violation in violations)}
            
            Current Metrics:
            • LCP: {metrics['lcp']:.2f}s
            • INP: {metrics['inp']:.0f}ms  
            • CLS: {metrics['cls']:.3f}
            
            Please investigate and optimize the page performance.
            """
            
            msg.attach(MimeText(body, 'plain'))
            
            server = smtplib.SMTP(self.config['email']['smtp_server'], self.config['email']['smtp_port'])
            server.starttls()
            server.login(self.config['email']['sender_email'], self.config['email']['sender_password'])
            server.send_message(msg)
            server.quit()
            
            print(f"Email alert sent for {url}")
            
        except Exception as e:
            print(f"Failed to send email alert: {str(e)}")
    
    def send_discord_alert(self, url: str, violations: List[str], metrics: Dict):
        """Send Discord alert for performance violations"""
        if not self.config['discord_webhook']:
            return
            
        try:
            embed = {
                "title": "🚨 Performance Alert",
                "description": f"Performance issues detected on **{url}**",
                "color": 15158332,  # Red color
                "fields": [
                    {
                        "name": "Performance Score",
                        "value": f"{metrics['performance_score']:.1f}/100",
                        "inline": True
                    },
                    {
                        "name": "Violations",
                        "value": "\n".join(f"• {violation}" for violation in violations),
                        "inline": False
                    },
                    {
                        "name": "Current Metrics",
                        "value": f"LCP: {metrics['lcp']:.2f}s\nINP: {metrics['inp']:.0f}ms\nCLS: {metrics['cls']:.3f}",
                        "inline": True
                    }
                ],
                "timestamp": datetime.now().isoformat(),
                "footer": {
                    "text": "VectorizeAI Performance Monitor"
                }
            }
            
            payload = {
                "embeds": [embed]
            }
            
            response = requests.post(self.config['discord_webhook'], json=payload)
            response.raise_for_status()
            
            print(f"Discord alert sent for {url}")
            
        except Exception as e:
            print(f"Failed to send Discord alert: {str(e)}")
    
    def monitor_all_urls(self):
        """Monitor all configured URLs"""
        print(f"Starting performance monitoring at {datetime.now()}")
        
        for url in self.config['urls_to_monitor']:
            print(f"Checking {url}...")
            
            metrics = self.get_pagespeed_metrics(url)
            if not metrics:
                continue
            
            violations = self.check_thresholds(metrics)
            
            if violations:
                print(f"⚠️  Violations found for {url}: {violations}")
                self.send_email_alert(url, violations, metrics)
                self.send_discord_alert(url, violations, metrics)
            else:
                print(f"✅ {url} - All metrics within thresholds")
            
            # Rate limiting
            time.sleep(2)
    
    def run_continuous_monitoring(self, interval_minutes: int = 30):
        """Run continuous monitoring with specified interval"""
        while True:
            try:
                self.monitor_all_urls()
                print(f"Next check in {interval_minutes} minutes...")
                time.sleep(interval_minutes * 60)
            except KeyboardInterrupt:
                print("Monitoring stopped by user")
                break
            except Exception as e:
                print(f"Error in monitoring loop: {str(e)}")
                time.sleep(60)  # Wait 1 minute before retrying

if __name__ == "__main__":
    monitor = PerformanceMonitor()
    
    # Run once for testing
    # monitor.monitor_all_urls()
    
    # Run continuous monitoring (every 30 minutes)
    monitor.run_continuous_monitoring(30)
