import bpy, os
scene=bpy.context.scene
scene.render.resolution_x=1280; scene.render.resolution_y=720; scene.render.resolution_percentage=100
scene.render.image_settings.file_format='PNG'
scene.render.filepath=r'C:\Users\Greg\AppData\Local\Temp\chris-forest-test-frame.png'
scene.frame_set(110)
bpy.ops.render.render(write_still=True)
