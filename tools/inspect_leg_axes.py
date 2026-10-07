import bpy
arm=bpy.data.objects['Chris_Rig']
for n in ['mixamorig:Hips','mixamorig:LeftUpLeg','mixamorig:LeftLeg','mixamorig:LeftFoot','mixamorig:RightUpLeg','mixamorig:RightLeg','mixamorig:RightFoot']:
 b=arm.data.bones[n]
 print(n,'head',tuple(round(v,3) for v in b.head_local),'tail',tuple(round(v,3) for v in b.tail_local),'mat',[[round(x,3) for x in r] for r in b.matrix_local])
