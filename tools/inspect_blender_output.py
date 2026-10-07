import bpy
print('render props', [p.identifier for p in bpy.types.RenderSettings.bl_rna.properties if 'format' in p.identifier])
print('image props', [p.identifier for p in bpy.types.ImageFormatSettings.bl_rna.properties])
print('render file format', getattr(bpy.context.scene.render,'file_format','NONE'))
print('engine',bpy.context.scene.render.engine)
