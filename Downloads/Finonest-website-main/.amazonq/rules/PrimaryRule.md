def check_existing_file(file_path):
    """Check if file already exists before creating new one"""
    import os
    
    if os.path.exists(file_path):
        return True
    return False

def create_new_file(file_path, content=''):
    """Create new file if it doesn't exist already"""
    if not check_existing_file(file_path):
        with open(file_path, 'w') as f:
            f.write(content)
        return True
    return False

def safe_file_operations(file_path, content=''):
    """Wrapper function to safely handle file operations"""
    if check_existing_file(file_path):
        print(f"File {file_path} already exists!")
        return False
    else:
        return create_new_file(file_path, content)