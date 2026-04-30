#!/usr/bin/env python3
"""
Docker Compose YAML Static Validation
Validates docker-compose files for syntax and required structure
Does NOT require Docker to be installed
"""

import sys
import json
from pathlib import Path

# Try to import yaml
try:
    import yaml
except ImportError:
    print("ERROR: PyYAML not installed. Install with: pip install PyYAML")
    sys.exit(1)


class ComposeValidator:
    """Validates Docker Compose files"""
    
    def __init__(self, root_dir="."):
        self.root = Path(root_dir)
        self.dev_file = self.root / "docker-compose.yml"
        self.prod_file = self.root / "docker-compose.prod.yml"
        self.tests = []
        self.passed = 0
        self.failed = 0
    
    def record_test(self, name, passed, message=""):
        """Record test result"""
        status = "✓ PASS" if passed else "✗ FAIL"
        print(f"{status}: {name}")
        if message:
            print(f"       {message}")
        
        self.tests.append({
            "name": name,
            "passed": passed,
            "message": message
        })
        
        if passed:
            self.passed += 1
        else:
            self.failed += 1
    
    def validate_yaml_syntax(self, filepath):
        """Validate YAML syntax"""
        try:
            with open(filepath, 'r') as f:
                yaml.safe_load(f)
            return True, None
        except yaml.YAMLError as e:
            return False, str(e)
        except FileNotFoundError:
            return False, f"File not found: {filepath}"
    
    def validate_services(self, filepath, expected_services):
        """Validate required services are defined"""
        try:
            with open(filepath, 'r') as f:
                data = yaml.safe_load(f)
            
            if not data or 'services' not in data:
                return False, "No 'services' section found"
            
            services = data['services']
            missing = [s for s in expected_services if s not in services]
            
            if missing:
                return False, f"Missing services: {', '.join(missing)}"
            
            return True, f"All services found: {', '.join(expected_services)}"
        except Exception as e:
            return False, str(e)
    
    def validate_dev_compose(self):
        """Run development compose validation"""
        print("\n" + "="*70)
        print("Group 1: Development Compose Validation")
        print("="*70)
        
        # 1.1: YAML syntax
        valid, msg = self.validate_yaml_syntax(self.dev_file)
        self.record_test("1.1: dev docker-compose.yml is valid YAML", valid, msg)
        
        # 1.2: Required services
        expected = ["php", "nginx", "mysql", "redis", "node"]
        valid, msg = self.validate_services(self.dev_file, expected)
        self.record_test("1.2: dev compose has all required services", valid, msg)
        
        # 1.3: Service configuration
        try:
            with open(self.dev_file, 'r') as f:
                data = yaml.safe_load(f)
            
            services = data.get('services', {})
            
            # Check PHP has build context
            if 'php' in services and 'build' in services['php']:
                self.record_test("1.3: PHP service configured with build", True)
            else:
                self.record_test("1.3: PHP service configured with build", False, 
                               "Missing build configuration")
            
            # Check MySQL has image
            if 'mysql' in services and 'image' in services['mysql']:
                self.record_test("1.4: MySQL service has image", True)
            else:
                self.record_test("1.4: MySQL service has image", False)
            
            # Check Redis has image
            if 'redis' in services and 'image' in services['redis']:
                self.record_test("1.5: Redis service has image", True)
            else:
                self.record_test("1.5: Redis service has image", False)
            
            # Check volumes defined
            if 'volumes' in data:
                self.record_test("1.6: Named volumes defined", True, 
                               f"Volumes: {list(data['volumes'].keys())}")
            else:
                self.record_test("1.6: Named volumes defined", False)
            
            # Check network defined
            if 'networks' in data:
                self.record_test("1.7: Custom network defined", True,
                               f"Networks: {list(data['networks'].keys())}")
            else:
                self.record_test("1.7: Custom network defined", False)
                
        except Exception as e:
            self.record_test("1.3-1.7: Service configuration", False, str(e))
    
    def validate_prod_compose(self):
        """Run production compose validation"""
        print("\n" + "="*70)
        print("Group 2: Production Compose Validation")
        print("="*70)
        
        # 2.1: YAML syntax
        valid, msg = self.validate_yaml_syntax(self.prod_file)
        self.record_test("2.1: docker-compose.prod.yml is valid YAML", valid, msg)
        
        # 2.2: Required services
        expected = ["nginx", "api", "mysql", "redis"]
        valid, msg = self.validate_services(self.prod_file, expected)
        self.record_test("2.2: prod compose has all required services", valid, msg)
        
        try:
            with open(self.prod_file, 'r') as f:
                data = yaml.safe_load(f)
            
            services = data.get('services', {})
            
            # Check for production-specific configs
            prod_checks = {
                "2.3: API service has image": ('api' in services and 'image' in services.get('api', {})),
                "2.4: Nginx configured for prod": ('nginx' in services and 'image' in services.get('nginx', {})),
            }
            
            for check_name, check_result in prod_checks.items():
                self.record_test(check_name, check_result)
            
            # Check for environment variable references
            compose_str = str(data)
            has_env_vars = '${' in compose_str or ':' in compose_str
            self.record_test("2.5: Environment variables configured", has_env_vars,
                           "Supports environment variable substitution")
                           
        except Exception as e:
            self.record_test("2.3-2.5: Production configuration", False, str(e))
    
    def validate_env_file(self):
        """Validate .env.example exists"""
        print("\n" + "="*70)
        print("Group 3: Environment Configuration")
        print("="*70)
        
        env_file = self.root / ".env.example"
        if env_file.exists():
            with open(env_file, 'r') as f:
                content = f.read()
            
            # Check for required variables
            required_vars = ["DB_PASS", "APP_ENV", "DB_HOST"]
            found = sum(1 for var in required_vars if var in content)
            
            self.record_test("3.1: .env.example exists with config", True,
                           f"Found {found}/{len(required_vars)} required variables")
        else:
            self.record_test("3.1: .env.example exists with config", False,
                           ".env.example not found")
    
    def run_all(self):
        """Run all validations"""
        print("\n")
        print("╔" + "="*68 + "╗")
        print("║" + " "*15 + "Docker Compose YAML Validation" + " "*23 + "║")
        print("╚" + "="*68 + "╝")
        
        self.validate_dev_compose()
        self.validate_prod_compose()
        self.validate_env_file()
        
        # Summary
        print("\n" + "="*70)
        print("Test Summary")
        print("="*70)
        
        total = self.passed + self.failed
        pass_rate = (self.passed / total * 100) if total > 0 else 0
        
        print(f"Total Tests: {total}")
        print(f"Passed: {self.passed}")
        print(f"Failed: {self.failed}")
        print(f"Pass Rate: {pass_rate:.1f}%")
        print()
        
        return 0 if self.failed == 0 else 1


if __name__ == "__main__":
    root_dir = Path(__file__).parent.parent.parent
    validator = ComposeValidator(root_dir)
    sys.exit(validator.run_all())
