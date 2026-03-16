import sys, os
workdir = sys.argv[1]
os.chdir(workdir)

video_path = 'video.mp4'

with open(video_path,'w') as f:
    f.write("fake video content")

print("Video rendered.")
