#! /usr/bin/env python3

import glob
import os
import subprocess
from string import Template

def read_file(filename):
    """Read a file, and get its contents."""
    with open(filename) as file:
            return file.read()

def write_file(filename, body):
    """Write a file, creating directories if necessary."""
    os.makedirs(os.path.dirname(filename), exist_ok=True)
    with open(filename, 'w') as file:
        file.write(body)

def get_partial_name(filename):
    """Get partial template id from filename."""
    return os.path.splitext(os.path.basename(filename))[0]

def get_partials():
    """Grab all partials for the Dockerfile templates."""
    partialFiles = glob.glob('./partials/*.docker')
    return {get_partial_name(f): read_file(f) for f in partialFiles}

def get_images():
    return sorted(os.listdir('./templates'))

def render_template(template_file, partials):
    """Using supplied partials, render a template."""
    template_body = read_file(template_file)
    template = Template(template_body)
    result = template.substitute(partials)
    return result

def build_template(partials, image):
    """Compile a template to the build directory and copies assets."""
    source = './templates/{}'.format(image)
    destination = './build/{}'.format(image)
    print('copying from {} to {}'.format(source, destination))
    os.system('cp -r {} {}'.format(source, destination))

    template_file = './build/{}/Dockerfile'.format(image)
    output = render_template(template_file, partials)
    write_file(template_file, output)

def build_image(image):
    """Build a given image."""
    tag = 'maxbucknell/{}'.format(image)
    build_directory = './build/{}'.format(image)
    os.system('docker build --no-cache -t {} {}'.format(tag, build_directory))

if __name__ == "__main__":
    os.system('rm -rf build')
    os.system('mkdir -p build')

    images = get_images()
    partials = get_partials()
    for image in images:
        print('Building image: {}'.format(image))
        build_template(partials, image)
        build_image(image)
